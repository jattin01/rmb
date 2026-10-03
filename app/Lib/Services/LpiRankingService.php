<?php

namespace App\Lib\Services;

use App\Models\SelectedOrder;
use App\Models\StructuralReference;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LPI v2 — Logistic Performance Index ranking.
 *
 * Implements the client's LPI document ("Volume controls the supply sequence"):
 *
 *   1. Every order is split into two zones:
 *        Main Battle      Q >= 50 m³
 *        Survival Battle  Q <  50 m³
 *   2. Each zone starts in descending volume.
 *   3. Top to bottom, each order may challenge only the order directly above it.
 *      A win moves it up exactly one place and ends its battle.
 *        Main Battle:     Top-10 customer AND non-flexible,
 *                         quantity >= 30% of the opponent (30% boundary),
 *                         pours faster than the opponent (speed = 480 / minutes per 8 m³).
 *        Survival Battle: quantity >= 30% of the opponent,
 *                         closer to the plant than the opponent. Speed is not checked.
 *   4. One promotion per order and per customer in each zone. An order that was
 *      already promoted cannot be challenged.
 *   5. Final sequence = Main Battle, then Survival Battle. Survival can never
 *      change the Main Battle.
 *   6. The LPI score is calculated afterwards for audit only (doc sections 2 and 16):
 *        V = 50 × quantity ÷ largest quantity of the day
 *        P = 15 (Top-10 customer) + 15 (non-flexible)
 *        C = 20 × speed ÷ fastest Main Battle speed of the day
 *        LPI = V + P + C   (out of 100)
 *
 * The scheduling engine places orders in `lpi_sequence` order (1 = first).
 * The score no longer decides the sequence.
 */
class LpiRankingService
{
    public const ZONE_MAIN     = 'main';
    public const ZONE_SURVIVAL = 'survival';

    /** Main Battle / Survival Battle boundary (doc sections 1, 4, 11). */
    public const MAIN_BATTLE_MIN_QTY = 50;

    /**
     * 30% volume boundary (doc sections 7, 13): the challenger's quantity
     * must be at least 30% of the order directly above it.
     */
    public const VOLUME_BOUNDARY_RATIO = 0.30;

    /** Customers with tier 1..10 are the Top-10 list (doc section 5). */
    public const TOP_CUSTOMER_MAX_TIER = 10;

    /** Speed = 480 ÷ minutes to pour 8 m³ (doc section 7). */
    public const SPEED_NUMERATOR = 480;

    /** Same fallback OrderController uses when a structure has no pouring time. */
    public const DEFAULT_POUR_MINUTES = 20;

    public const V_WEIGHT       = 50;
    public const P_TOP_CUSTOMER = 15;
    public const P_NON_FLEXIBLE = 15;
    public const C_WEIGHT       = 20;

    /**
     * Rank every selected order of the shift and persist the result.
     * Runs before every Generate, after the dispatcher has marked
     * non-flexible orders (doc section 17).
     *
     * @return array<int, array> ranked cases, in final sequence
     */
    public function rankAndStore(int|string $groupCompanyId, int $userId, string $shiftStart, string $shiftEnd): array
    {
        $orders = SelectedOrder::with('customer_company')
            ->where('group_company_id', $groupCompanyId)
            ->where('user_id', $userId)
            ->whereBetween('delivery_date', [$shiftStart, $shiftEnd])
            ->where('selected', true)
            ->get();

        $structures = StructuralReference::whereIn('id', $orders->pluck('structural_reference_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $cases = [];
        $unranked = [];
        foreach ($orders as $order) {
            // Only orders with 0 < Q take part in the battles (doc sections 4, 11).
            if ((float) $order->quantity <= 0) {
                $unranked[] = $order->id;
                continue;
            }
            $cases[] = $this->toCase($order, $structures[$order->structural_reference_id] ?? null);
        }

        $ranked = $this->rank($cases);

        $this->store($ranked);
        $this->clear($unranked);
        $this->logAudit($ranked);

        return $ranked;
    }

    /**
     * Builds the battle input for one order.
     */
    public function toCase(SelectedOrder $order, ?StructuralReference $structure): array
    {
        $tier = (int) ($order->customer_company->tier ?? 999);

        $pourMinutes = (float) ($order->pump
            ? ($structure->pouring_w_pump_time ?? 0)
            : ($structure->pouring_wo_pump_time ?? 0));
        if ($pourMinutes <= 0) {
            $pourMinutes = (float) ($order->pouring_time ?: self::DEFAULT_POUR_MINUTES);
        }

        return [
            'id'           => $order->id,
            'order_no'     => $order->order_no,
            'customer'     => $order->customer_id ? 'id:' . $order->customer_id : 'name:' . $order->customer,
            'quantity'     => (float) $order->quantity,
            'top_customer' => $tier >= 1 && $tier <= self::TOP_CUSTOMER_MAX_TIER,
            'non_flexible' => !((int) ($order->flexibility ?? 1)),
            'pour_minutes' => $pourMinutes,
            'speed'        => round(self::SPEED_NUMERATOR / $pourMinutes, 2),
            'distance'     => (int) ($order->travel_to_site ?? 0),
        ];
    }

    /**
     * Pure ranking: applies the battle rules and the audit score.
     *
     * Each case needs: id, order_no, customer, quantity, top_customer,
     * non_flexible, speed, distance. Returns the cases in final sequence with
     * sequence, initial_position, zone, promoted, reason, v, p, c, lpi added.
     */
    public function rank(array $cases): array
    {
        $main     = [];
        $survival = [];
        foreach ($cases as $case) {
            if ($case['quantity'] >= self::MAIN_BATTLE_MIN_QTY) {
                $main[] = $case + ['zone' => self::ZONE_MAIN];
            } else {
                $survival[] = $case + ['zone' => self::ZONE_SURVIVAL];
            }
        }

        $main     = $this->battle($this->volumeRanking($main), self::ZONE_MAIN);
        $survival = $this->battle($this->volumeRanking($survival), self::ZONE_SURVIVAL);

        $ranked = array_merge($main, $survival);

        $maxQty       = max([0, ...array_column($ranked, 'quantity')]);
        $fastestSpeed = max([0, ...array_column($main, 'speed')]);
        if ($fastestSpeed <= 0) {
            // No Main Battle order today: fall back to the fastest order overall.
            $fastestSpeed = max([0, ...array_column($ranked, 'speed')]);
        }

        foreach ($ranked as $i => &$case) {
            $case['sequence'] = $i + 1;

            $v = $maxQty > 0 ? round(self::V_WEIGHT * $case['quantity'] / $maxQty, 2) : 0.0;
            $p = ($case['top_customer'] ? self::P_TOP_CUSTOMER : 0)
                + ($case['non_flexible'] ? self::P_NON_FLEXIBLE : 0);
            $c = $fastestSpeed > 0 ? round(min(self::C_WEIGHT, self::C_WEIGHT * $case['speed'] / $fastestSpeed), 2) : 0.0;

            $case['v']   = $v;
            $case['p']   = (float) $p;
            $case['c']   = $c;
            $case['lpi'] = round($v + $p + $c, 2);
        }
        unset($case);

        return $ranked;
    }

    /**
     * Largest volume first (doc sections 4.1, 12). Ties keep a stable order by id.
     */
    private function volumeRanking(array $cases): array
    {
        usort($cases, fn($a, $b) => [$b['quantity'], $a['id']] <=> [$a['quantity'], $b['id']]);

        foreach ($cases as $i => &$case) {
            $case['initial_position'] = $i + 1;
            $case['promoted']         = false;
            $case['reason']           = null;
        }
        unset($case);

        return $cases;
    }

    /**
     * One pass, top to bottom. Each order challenges only the order directly
     * above it; a win swaps the two and ends that order's battle (doc sections 6, 14).
     */
    private function battle(array $cases, string $zone): array
    {
        $promotedCustomers = [];

        for ($i = 1; $i < count($cases); $i++) {
            $challenger = $cases[$i];
            $opponent   = $cases[$i - 1];

            // Doc section 9: one promotion per order and per customer in each zone,
            // and an order that was already promoted cannot be challenged.
            if ($opponent['promoted'] || isset($promotedCustomers[$challenger['customer']])) {
                continue;
            }

            $reason = $zone === self::ZONE_MAIN
                ? $this->mainBattleWin($challenger, $opponent)
                : $this->survivalBattleWin($challenger, $opponent);

            if ($reason === null) {
                continue;
            }

            $challenger['promoted'] = true;
            $challenger['reason']   = "promoted over {$opponent['order_no']} because {$reason}";
            $promotedCustomers[$challenger['customer']] = true;

            $cases[$i - 1] = $challenger;
            $cases[$i]     = $opponent;
        }

        return $cases;
    }

    /**
     * Main Battle (doc section 7). Returns the reason on a win, null otherwise.
     */
    private function mainBattleWin(array $challenger, array $opponent): ?string
    {
        if (!($challenger['top_customer'] && $challenger['non_flexible'])) {
            return null;
        }
        if (!$this->withinVolumeBoundary($challenger, $opponent)) {
            return null;
        }
        if (!($challenger['speed'] > $opponent['speed'])) {
            return null;
        }

        return sprintf(
            'Top-10 customer and non-flexible, %s m³ is %d%% of %s m³, and speed %s > %s',
            $this->qty($challenger['quantity']),
            $this->volumePercent($challenger, $opponent),
            $this->qty($opponent['quantity']),
            $challenger['speed'],
            $opponent['speed']
        );
    }

    /**
     * Survival Battle (doc section 13). Speed, structure and pump are not checked.
     */
    private function survivalBattleWin(array $challenger, array $opponent): ?string
    {
        if (!$this->withinVolumeBoundary($challenger, $opponent)) {
            return null;
        }
        if (!($challenger['distance'] < $opponent['distance'])) {
            return null;
        }

        return sprintf(
            '%s m³ is %d%% of %s m³ and it is closer to the plant (%d min < %d min)',
            $this->qty($challenger['quantity']),
            $this->volumePercent($challenger, $opponent),
            $this->qty($opponent['quantity']),
            $challenger['distance'],
            $opponent['distance']
        );
    }

    private function withinVolumeBoundary(array $challenger, array $opponent): bool
    {
        return $opponent['quantity'] > 0
            && $challenger['quantity'] / $opponent['quantity'] >= self::VOLUME_BOUNDARY_RATIO;
    }

    private function volumePercent(array $challenger, array $opponent): int
    {
        return (int) floor(100 * $challenger['quantity'] / $opponent['quantity']);
    }

    private function qty(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 2, '.', ''), '0'), '.');
    }

    private function clear(array $orderIds): void
    {
        if (empty($orderIds)) {
            return;
        }

        try {
            DB::table('selected_orders')->whereIn('id', $orderIds)->update([
                'lpi_sequence'         => null,
                'lpi_initial_position' => null,
                'lpi_zone'             => null,
                'lpi_promoted'         => false,
                'lpi_reason'           => null,
                'lpi_speed'            => null,
                'lpi_v'                => null,
                'lpi_p'                => null,
                'lpi_c'                => null,
                'lpi_score'            => null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[LPI] Could not clear LPI ranking — run migrations first. ' . $e->getMessage());
        }
    }

    private function store(array $ranked): void
    {
        foreach ($ranked as $case) {
            try {
                DB::table('selected_orders')
                    ->where('id', $case['id'])
                    ->update([
                        'lpi_sequence'         => $case['sequence'],
                        'lpi_initial_position' => $case['initial_position'],
                        'lpi_zone'             => $case['zone'],
                        'lpi_promoted'         => $case['promoted'],
                        'lpi_reason'           => $case['reason'],
                        'lpi_speed'            => $case['speed'],
                        'lpi_v'                => $case['v'],
                        'lpi_p'                => $case['p'],
                        'lpi_c'                => $case['c'],
                        'lpi_score'            => $case['lpi'],
                    ]);
            } catch (\Throwable $e) {
                Log::warning("[LPI] Could not save LPI ranking for order_id={$case['id']} — run migrations first. " . $e->getMessage());
            }
        }
    }

    /**
     * Audit trail: every order's position, zone, speed, distance, V/P/C and
     * the reason for any promotion.
     */
    private function logAudit(array $ranked): void
    {
        foreach ($ranked as $case) {
            Log::info(sprintf(
                '[LPI] #%d %s zone=%s start_pos=%d qty=%s top10=%s non_flexible=%s speed=%s distance=%dmin '
                    . 'V=%s P=%s C=%s LPI=%s%s',
                $case['sequence'],
                $case['order_no'],
                $case['zone'],
                $case['initial_position'],
                $this->qty($case['quantity']),
                $case['top_customer'] ? 'yes' : 'no',
                $case['non_flexible'] ? 'yes' : 'no',
                $case['speed'],
                $case['distance'],
                $case['v'],
                $case['p'],
                $case['c'],
                $case['lpi'],
                $case['reason'] ? ' — ' . $case['reason'] : ''
            ));
        }
    }
}

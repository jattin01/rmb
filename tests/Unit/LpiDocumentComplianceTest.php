<?php

namespace Tests\Unit;

use App\Lib\Services\LpiRankingService;
use App\Models\SelectedOrder;
use App\Models\StructuralReference;
use PHPUnit\Framework\TestCase;

/**
 * Checks LpiRankingService against the client's LPI document
 * (lpi_algorithm.docx), one test per rule, numbered by document section.
 */
class LpiDocumentComplianceTest extends TestCase
{
    private const QUALIFIED = ['top_customer' => true, 'non_flexible' => true];

    private function order(string $no, float $qty, array $overrides = []): array
    {
        static $id = 1000;

        return $overrides + [
            'id'           => ++$id,
            'order_no'     => $no,
            'customer'     => 'name:' . $no,
            'quantity'     => $qty,
            'top_customer' => false,
            'non_flexible' => false,
            'speed'        => 8.0,
            'distance'     => 30,
        ];
    }

    private function rank(array $cases): array
    {
        return (new LpiRankingService())->rank($cases);
    }

    private function sequence(array $ranked): array
    {
        return array_column($ranked, 'order_no');
    }

    private function byNo(array $ranked): array
    {
        return array_column($ranked, null, 'order_no');
    }

    // ── Section 1, 4, 11: the 50 m³ boundary ────────────────────────────────

    public function test_s1_exactly_50_is_main_and_below_50_is_survival(): void
    {
        $byNo = $this->byNo($this->rank([
            $this->order('M50', 50),
            $this->order('S49', 49.99),
        ]));

        $this->assertSame('main', $byNo['M50']['zone']);
        $this->assertSame('survival', $byNo['S49']['zone']);
    }

    // ── Section 4.1 / 12: initial ranking by descending volume ──────────────

    public function test_s4_1_document_table_without_promotions_is_pure_volume_order(): void
    {
        $ranked = $this->rank([
            $this->order('C', 80),
            $this->order('E', 50),
            $this->order('A', 120),
            $this->order('D', 65),
            $this->order('B', 100),
        ]);

        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->sequence($ranked));
        $this->assertSame([1, 2, 3, 4, 5], array_column($ranked, 'initial_position'));
        $this->assertSame([false, false, false, false, false], array_column($ranked, 'promoted'));
    }

    public function test_s12_survival_starts_in_descending_volume(): void
    {
        $ranked = $this->rank([
            $this->order('S10', 10),
            $this->order('S40', 40),
            $this->order('S25', 25),
        ]);

        $this->assertSame(['S40', 'S25', 'S10'], $this->sequence($ranked));
    }

    // ── Section 5 / 7 condition 1: Top-10 AND Non-Flexible ──────────────────

    public function test_s5_priority_needs_both_flags(): void
    {
        foreach ([
            'neither'       => [],
            'only top-10'   => ['top_customer' => true],
            'only non-flex' => ['non_flexible' => true],
        ] as $label => $flags) {
            $ranked = $this->rank([
                $this->order('A', 100),
                $this->order('B', 90, $flags + ['speed' => 48.0]),
            ]);
            $this->assertSame(['A', 'B'], $this->sequence($ranked), $label);
        }

        $ranked = $this->rank([
            $this->order('A', 100),
            $this->order('B', 90, self::QUALIFIED + ['speed' => 48.0]),
        ]);
        $this->assertSame(['B', 'A'], $this->sequence($ranked), 'both flags');
    }

    public function test_s5_p_is_15_per_flag_for_audit(): void
    {
        $byNo = $this->byNo($this->rank([
            $this->order('NONE', 100),
            $this->order('TOP', 90, ['top_customer' => true]),
            $this->order('NF', 80, ['non_flexible' => true]),
            $this->order('BOTH', 70, self::QUALIFIED),
        ]));

        $this->assertEquals(0.0, $byNo['NONE']['p']);
        $this->assertEquals(15.0, $byNo['TOP']['p']);
        $this->assertEquals(15.0, $byNo['NF']['p']);
        $this->assertEquals(30.0, $byNo['BOTH']['p']);
    }

    // ── Section 7 condition 2: 30% volume boundary (Main) ───────────────────

    public function test_s7_main_30_percent_boundary_inclusive(): void
    {
        $at = $this->rank([
            $this->order('A', 200),
            $this->order('B', 60, self::QUALIFIED + ['speed' => 48.0]),
        ]);
        $this->assertSame(['B', 'A'], $this->sequence($at), '60 is exactly 30% of 200');

        $below = $this->rank([
            $this->order('A', 200),
            $this->order('B', 59.9, self::QUALIFIED + ['speed' => 48.0]),
        ]);
        $this->assertSame(['A', 'B'], $this->sequence($below), '59.9 is under 30% of 200');
    }

    // ── Section 7 condition 3: speed must be strictly faster ────────────────

    public function test_s7_main_needs_strictly_faster_speed(): void
    {
        $slower = $this->rank([
            $this->order('A', 100, ['speed' => 24.0]),
            $this->order('B', 90, self::QUALIFIED + ['speed' => 12.0]),
        ]);
        $this->assertSame(['A', 'B'], $this->sequence($slower));

        $equal = $this->rank([
            $this->order('A', 100, ['speed' => 24.0]),
            $this->order('B', 90, self::QUALIFIED + ['speed' => 24.0]),
        ]);
        $this->assertSame(['A', 'B'], $this->sequence($equal));
    }

    public function test_s7_main_ignores_distance(): void
    {
        // Much closer to the plant, but not faster: no promotion in the Main Battle.
        $ranked = $this->rank([
            $this->order('A', 100, ['distance' => 90]),
            $this->order('B', 90, self::QUALIFIED + ['distance' => 5, 'speed' => 8.0]),
        ]);
        $this->assertSame(['A', 'B'], $this->sequence($ranked));
    }

    // ── Section 6 / 14: one adjacent position, then stop ────────────────────

    public function test_s6_a_winner_moves_one_place_only_even_if_it_beats_everyone(): void
    {
        $ranked = $this->rank([
            $this->order('A', 120),
            $this->order('B', 110),
            $this->order('C', 100, self::QUALIFIED + ['speed' => 48.0]),
        ]);

        // C would beat A too, but stops after passing B.
        $this->assertSame(['A', 'C', 'B'], $this->sequence($ranked));
        $this->assertSame(3, $this->byNo($ranked)['C']['initial_position']);
    }

    public function test_s14_survival_winner_moves_one_place_only(): void
    {
        $ranked = $this->rank([
            $this->order('S1', 45, ['distance' => 40]),
            $this->order('S2', 40, ['distance' => 50]),
            $this->order('S3', 35, ['distance' => 5]),
        ]);

        $this->assertSame(['S1', 'S3', 'S2'], $this->sequence($ranked));
    }

    // ── Section 9: one promotion per customer, per zone, independently ──────

    public function test_s9_customer_cap_in_survival(): void
    {
        $ranked = $this->rank([
            $this->order('S1', 45, ['distance' => 60]),
            $this->order('S2', 40, ['distance' => 10, 'customer' => 'id:9']),
            $this->order('S3', 35, ['distance' => 60]),
            $this->order('S4', 30, ['distance' => 10, 'customer' => 'id:9']),
        ]);

        // S2 wins for customer 9; S4 (same customer) may not win again.
        $this->assertSame(['S2', 'S1', 'S3', 'S4'], $this->sequence($ranked));
    }

    public function test_s9_main_and_survival_caps_are_independent(): void
    {
        $ranked = $this->rank([
            $this->order('A', 100),
            $this->order('B', 90, self::QUALIFIED + ['customer' => 'id:5', 'speed' => 48.0]),
            $this->order('S1', 40, ['distance' => 60]),
            $this->order('S2', 30, ['customer' => 'id:5', 'distance' => 5]),
        ]);

        // Customer 5 gets one Main promotion AND one Survival promotion.
        $this->assertSame(['B', 'A', 'S2', 'S1'], $this->sequence($ranked));
    }

    // ── Section 10 / 15: Survival can never touch the Main Battle ───────────

    public function test_s15_survival_never_passes_the_last_main_order(): void
    {
        $ranked = $this->rank([
            $this->order('M1', 100),
            $this->order('M2', 50, ['distance' => 120, 'speed' => 2.0]),
            $this->order('S1', 49, self::QUALIFIED + ['distance' => 1, 'speed' => 96.0]),
        ]);

        $this->assertSame(['M1', 'M2', 'S1'], $this->sequence($ranked));
        $this->assertFalse($this->byNo($ranked)['S1']['promoted']);
    }

    // ── Section 13: Survival = 30% + closer distance, speed not checked ─────

    public function test_s13_survival_30_percent_boundary_inclusive(): void
    {
        $at = $this->rank([
            $this->order('S1', 40, ['distance' => 60]),
            $this->order('S2', 12, ['distance' => 10]),
        ]);
        $this->assertSame(['S2', 'S1'], $this->sequence($at), '12 is exactly 30% of 40');

        $below = $this->rank([
            $this->order('S1', 40, ['distance' => 60]),
            $this->order('S2', 11.9, ['distance' => 10]),
        ]);
        $this->assertSame(['S1', 'S2'], $this->sequence($below), '11.9 is under 30% of 40');
    }

    public function test_s13_survival_needs_strictly_closer_distance(): void
    {
        $equal = $this->rank([
            $this->order('S1', 40, ['distance' => 30]),
            $this->order('S2', 30, ['distance' => 30]),
        ]);
        $this->assertSame(['S1', 'S2'], $this->sequence($equal));
    }

    public function test_s13_survival_ignores_speed_and_priority(): void
    {
        // Faster, Top-10 and non-flexible, but farther: no promotion.
        $farther = $this->rank([
            $this->order('S1', 40, ['distance' => 20, 'speed' => 4.0]),
            $this->order('S2', 30, self::QUALIFIED + ['distance' => 40, 'speed' => 96.0]),
        ]);
        $this->assertSame(['S1', 'S2'], $this->sequence($farther));

        // Slower and not a priority customer, but closer: promoted.
        $closer = $this->rank([
            $this->order('S1', 40, ['distance' => 40, 'speed' => 96.0]),
            $this->order('S2', 30, ['distance' => 20, 'speed' => 4.0]),
        ]);
        $this->assertSame(['S2', 'S1'], $this->sequence($closer));
    }

    // ── Section 2, 8, 16: LPI score is audit only, C is speed-only ──────────

    public function test_s2_a_higher_lpi_score_does_not_change_the_sequence(): void
    {
        $ranked = $this->rank([
            $this->order('A', 100, ['speed' => 4.0]),
            // Higher score (P = 30) but not faster than A, so no promotion.
            $this->order('B', 95, self::QUALIFIED + ['speed' => 4.0]),
        ]);
        $byNo = $this->byNo($ranked);

        $this->assertGreaterThan($byNo['A']['lpi'], $byNo['B']['lpi']);
        $this->assertSame(['A', 'B'], $this->sequence($ranked));
    }

    public function test_s8_c_reference_is_fastest_main_speed_and_ignores_distance(): void
    {
        $byNo = $this->byNo($this->rank([
            $this->order('M1', 100, ['speed' => 24.0, 'distance' => 5]),
            $this->order('M2', 80, ['speed' => 12.0, 'distance' => 90]),
            $this->order('S1', 40, ['speed' => 48.0]),
        ]));

        $this->assertEquals(20.0, $byNo['M1']['c']);  // fastest Main = reference
        $this->assertEquals(10.0, $byNo['M2']['c']);  // half the speed = half of 20, distance irrelevant
        $this->assertEquals(20.0, $byNo['S1']['c']);  // faster than the reference is capped at 20
    }

    public function test_s16_lpi_is_50v_plus_30p_plus_20c(): void
    {
        $byNo = $this->byNo($this->rank([
            $this->order('A', 100, ['speed' => 24.0]),
            $this->order('B', 50, self::QUALIFIED + ['speed' => 12.0]),
        ]));

        $this->assertEquals(50 + 0 + 20, $byNo['A']['lpi']);
        $this->assertEquals(25 + 30 + 10, $byNo['B']['lpi']);
    }

    // ── Section 17: full flow, both zones together ──────────────────────────

    public function test_s17_full_day(): void
    {
        $ranked = $this->rank([
            $this->order('A', 120, ['speed' => 16.0]),
            $this->order('B', 100, ['speed' => 16.0]),
            $this->order('C', 80, ['speed' => 16.0]),
            $this->order('D', 65, self::QUALIFIED + ['speed' => 24.0]),   // beats C
            $this->order('E', 50, self::QUALIFIED + ['speed' => 48.0]),   // faces C (demoted), beats it
            $this->order('F', 45, ['distance' => 40]),
            $this->order('G', 30, ['distance' => 10]),                       // beats F
            $this->order('H', 20, ['distance' => 50]),                       // faces F, farther: stays
            $this->order('I', 5, ['distance' => 1]),                         // 5 / 20 = 25%: stays
        ]);

        $this->assertSame(['A', 'B', 'D', 'E', 'C', 'G', 'F', 'H', 'I'], $this->sequence($ranked));
        $this->assertSame(range(1, 9), array_column($ranked, 'sequence'));
        $this->assertSame(
            ['main', 'main', 'main', 'main', 'main', 'survival', 'survival', 'survival', 'survival'],
            array_column($ranked, 'zone')
        );
    }

    // ── Section 3, 5, 7: converting a real order into a battle case ─────────

    private function selectedOrder(array $attributes, ?int $tier): SelectedOrder
    {
        $order = new SelectedOrder();
        $order->forceFill($attributes + [
            'id'           => 1,
            'order_no'     => 'X',
            'customer_id'  => 3,
            'quantity'     => 60,
            'pump'         => null,
            'flexibility'  => null,
            'pouring_time' => null,
            'travel_to_site' => 25,
        ]);
        // The Customer model needs the Laravel app to boot; toCase() only reads ->tier.
        $order->setRelation('customer_company', $tier === null ? null : (object) ['tier' => $tier]);

        return $order;
    }

    private function structure(float $withPump, float $withoutPump): StructuralReference
    {
        $structure = new StructuralReference();
        $structure->forceFill(['pouring_w_pump_time' => $withPump, 'pouring_wo_pump_time' => $withoutPump]);

        return $structure;
    }

    public function test_s3_orders_are_flexible_unless_marked_non_flexible(): void
    {
        $service = new LpiRankingService();

        $this->assertFalse($service->toCase($this->selectedOrder(['flexibility' => null], 1), null)['non_flexible']);
        $this->assertFalse($service->toCase($this->selectedOrder(['flexibility' => 1], 1), null)['non_flexible']);
        $this->assertTrue($service->toCase($this->selectedOrder(['flexibility' => 0], 1), null)['non_flexible']);
    }

    public function test_s5_top_10_is_customer_tier_1_to_10(): void
    {
        $service = new LpiRankingService();

        $this->assertTrue($service->toCase($this->selectedOrder([], 1), null)['top_customer']);
        $this->assertTrue($service->toCase($this->selectedOrder([], 10), null)['top_customer']);
        $this->assertFalse($service->toCase($this->selectedOrder([], 11), null)['top_customer']);
        $this->assertFalse($service->toCase($this->selectedOrder([], 0), null)['top_customer']);
        $this->assertFalse($service->toCase($this->selectedOrder([], null), null)['top_customer']);
    }

    public function test_s7_speed_is_480_over_structure_minutes_for_pump_or_no_pump(): void
    {
        $service = new LpiRankingService();
        $structure = $this->structure(withPump: 40, withoutPump: 60);

        $this->assertEquals(12.0, $service->toCase($this->selectedOrder(['pump' => 7], 1), $structure)['speed']);
        $this->assertEquals(8.0, $service->toCase($this->selectedOrder(['pump' => null], 1), $structure)['speed']);

        // No time in the structure table: the order's pouring time, then the 20 min default.
        $empty = $this->structure(0, 0);
        $this->assertEquals(16.0, $service->toCase($this->selectedOrder(['pouring_time' => 30], 1), $empty)['speed']);
        $this->assertEquals(24.0, $service->toCase($this->selectedOrder([], 1), null)['speed']);
    }

    public function test_s13_distance_is_travel_time_to_site(): void
    {
        $case = (new LpiRankingService())->toCase($this->selectedOrder(['travel_to_site' => 42], 1), null);

        $this->assertSame(42, $case['distance']);
    }
}

<?php

namespace Tests\Unit;

use App\Lib\Services\LpiRankingService;
use PHPUnit\Framework\TestCase;

class LpiRankingServiceTest extends TestCase
{
    private function order(string $no, float $qty, array $overrides = []): array
    {
        static $id = 0;

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

    private function sequence(array $ranked): array
    {
        return array_column($ranked, 'order_no');
    }

    /** Initial ranking example from the LPI document, section 4.1. */
    public function test_document_example_volume_ranking_and_one_promotion(): void
    {
        $qualified = ['top_customer' => true, 'non_flexible' => true];

        $ranked = (new LpiRankingService())->rank([
            $this->order('E', 50),
            $this->order('C', 80),
            $this->order('A', 120),
            $this->order('D', 65, $qualified + ['speed' => 24.0]),
            $this->order('B', 100),
        ]);

        // Starts A, B, C, D, E by volume; D beats C (Top-10 + non-flexible,
        // 65 is >= 30% of 80, faster) and stops after one place.
        $this->assertSame(['A', 'B', 'D', 'C', 'E'], $this->sequence($ranked));
        $this->assertSame([1, 2, 3, 4, 5], array_column($ranked, 'sequence'));

        $byNo = array_column($ranked, null, 'order_no');
        $this->assertSame(4, $byNo['D']['initial_position']);
        $this->assertTrue($byNo['D']['promoted']);
        $this->assertStringStartsWith('promoted over C because', $byNo['D']['reason']);
        $this->assertSame('main', $byNo['E']['zone']);

        // Audit (section 16): V 50% of largest volume, P 15 + 15, C 20 × speed ÷ fastest Main speed.
        $this->assertEquals(50.0, $byNo['A']['v']);
        $this->assertEquals(0.0, $byNo['A']['p']);
        $this->assertEquals(6.67, $byNo['A']['c']);
        $this->assertEquals(56.67, $byNo['A']['lpi']);
        $this->assertEquals(27.08 + 30 + 20, $byNo['D']['lpi']);
    }

    public function test_main_battle_needs_top_customer_and_non_flexible(): void
    {
        $service = new LpiRankingService();

        $notTop = $service->rank([
            $this->order('A', 100),
            $this->order('B', 90, ['non_flexible' => true, 'speed' => 24.0]),
        ]);
        $this->assertSame(['A', 'B'], $this->sequence($notTop));

        $flexible = $service->rank([
            $this->order('A', 100),
            $this->order('B', 90, ['top_customer' => true, 'speed' => 24.0]),
        ]);
        $this->assertSame(['A', 'B'], $this->sequence($flexible));
    }

    public function test_main_battle_needs_volume_boundary_and_faster_speed(): void
    {
        $service = new LpiRankingService();
        $qualified = ['top_customer' => true, 'non_flexible' => true];

        $belowBoundary = $service->rank([
            $this->order('A', 200),
            $this->order('B', 59, $qualified + ['speed' => 24.0]),
        ]);
        $this->assertSame(['A', 'B'], $this->sequence($belowBoundary));

        $atBoundary = $service->rank([
            $this->order('A', 200),
            $this->order('B', 60, $qualified + ['speed' => 24.0]),
        ]);
        $this->assertSame(['B', 'A'], $this->sequence($atBoundary));

        $sameSpeed = $service->rank([
            $this->order('A', 100),
            $this->order('B', 90, $qualified + ['speed' => 8.0]),
        ]);
        $this->assertSame(['A', 'B'], $this->sequence($sameSpeed));
    }

    public function test_one_place_only_then_next_order_faces_the_demoted_order(): void
    {
        $qualified = ['top_customer' => true, 'non_flexible' => true];

        $ranked = (new LpiRankingService())->rank([
            $this->order('A', 100),
            $this->order('B', 95),
            $this->order('C', 90, $qualified + ['speed' => 24.0]),
            $this->order('D', 85, $qualified + ['speed' => 48.0]),
        ]);

        // C beats B and stops: it moves one place, never past A.
        // D now faces B (directly above it, not promoted) and wins.
        $this->assertSame(['A', 'C', 'D', 'B'], $this->sequence($ranked));
        $this->assertSame([false, true, true, false], array_column($ranked, 'promoted'));
    }

    public function test_one_promotion_per_customer_per_zone(): void
    {
        $qualified = ['top_customer' => true, 'non_flexible' => true, 'customer' => 'id:7', 'speed' => 24.0];

        $ranked = (new LpiRankingService())->rank([
            $this->order('A', 100),
            $this->order('B', 95, $qualified),
            $this->order('C', 90),
            $this->order('D', 85, $qualified),
        ]);

        $this->assertSame(['B', 'A', 'C', 'D'], $this->sequence($ranked));
    }

    public function test_survival_uses_distance_and_30_percent_not_speed_and_never_passes_main(): void
    {
        $service = new LpiRankingService();

        $ranked = $service->rank([
            $this->order('M', 50),
            $this->order('S1', 49, ['distance' => 30, 'speed' => 48.0]),
            $this->order('S2', 15, ['distance' => 10, 'speed' => 4.0]),
        ]);
        $this->assertSame(['M', 'S2', 'S1'], $this->sequence($ranked));
        $this->assertSame('survival', $ranked[1]['zone']);

        $belowBoundary = $service->rank([
            $this->order('S1', 49, ['distance' => 30]),
            $this->order('S2', 14, ['distance' => 10]),
        ]);
        $this->assertSame(['S1', 'S2'], $this->sequence($belowBoundary));
    }
}

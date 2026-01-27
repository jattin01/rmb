<?php 
namespace App\Services;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\BatchingPlant;
use App\Models\OrderSchedule;
use App\Models\OrderPumpSchedule;
use App\Models\BatchingPlantOccupancy;

class OrderSchedulingService
{
    public function schedule(Order $order)
    {
        // 1. Pump first
        $pumpSchedule = $this->schedulePump($order);

        // 2. Then batching
        $plant = $this->findAvailablePlant($pumpSchedule->start_time);

        $start = Carbon::parse($pumpSchedule->start_time)
                        ->addMinutes($order->buffer_minutes);

        $end = (clone $start)->addMinutes($order->mixing_time);

        // 3. Save order schedule
        $schedule = OrderSchedule::create([
            'order_id' => $order->id,
            'schedule_start' => $start,
            'schedule_end' => $end,
        ]);

        // 4. Mark plant occupied
        BatchingPlantOccupancy::create([
            'batching_plant_id' => $plant->id,
            'start_time' => $start,
            'end_time' => $end,
        ]);

        $order->update(['status' => 'scheduled']);

        return $schedule;
    }

    protected function schedulePump(Order $order)
    {
        return OrderPumpSchedule::create([
            'order_id' => $order->id,
            'start_time' => now()->addMinutes(30),
            'end_time' => now()->addMinutes(90),
        ]);
    }

    protected function findAvailablePlant($time)
    {
        return BatchingPlant::whereDoesntHave('occupancies', function ($q) use ($time) {
            $q->where('start_time', '<=', $time)
              ->where('end_time', '>=', $time);
        })->firstOrFail();
    }
}

<?php

namespace App\Jobs;

use App\Lib\Services\ScheduleService;
use App\Models\SelectedOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


/**
 * Runs the (potentially slow) schedule generation off the HTTP request, on the
 * queue. Status is tracked in the `schedule_runs` row identified by $runId so the
 * front-end can poll for completion.
 */
class GenerateScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Scheduling is long-running; allow up to 30 minutes and do NOT auto-retry a
     * heavy run (a retry would just re-clear and rebuild the same tables).
     */
    public int $timeout = 0;
    public int $tries   = 1;

    public function __construct(
        public int $runId,
        public int $userId,
        public int $companyId,
        public string $scheduleDate,
        public array $transitMixers,
        public array $pumps,
        public array $batchingPlants,
        public string $shiftStart,
        public string $shiftEnd,
        public int $intervalDeviation
    ) {
    }

    /**
     * Never let two generations for the SAME company + date run at once — they
     * clear and rewrite the same shared tables. Other company/date runs are free
     * to run in parallel.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("schedule:{$this->companyId}:{$this->scheduleDate}"))
                ->releaseAfter(30)
                ->expireAfter(1900),
        ];
    }

    public function handle(): void
    {
        DB::table('schedule_runs')->where('id', $this->runId)->update([
            'status'     => 'processing',
            'progress'   => 0,
            'started_at' => now(),
            'message'    => null,
            'updated_at' => now(),
        ]);
        SelectedOrder::where('group_company_id', $this->companyId)
            ->where('user_id', $this->userId)
            ->whereDate('delivery_date', $this->scheduleDate)
            ->update([
            'schedule_status'     => 'processing',
        ]);

        try {
            (new ScheduleService())->initializeSchedule(
                $this->userId,
                $this->companyId,
                $this->scheduleDate,
                $this->transitMixers,
                $this->pumps,
                $this->batchingPlants,
                "",
                $this->shiftStart,
                $this->shiftEnd,
                $this->intervalDeviation
            );

            DB::table('schedule_runs')->where('id', $this->runId)->update([
                'status'      => 'completed',
                 'progress'    => 100,
                'finished_at' => now(),
                'message'     => null,
                'updated_at'  => now(),
            ]);
              SelectedOrder::where('group_company_id', $this->companyId)
            ->where('user_id', $this->userId)
            ->whereDate('delivery_date', $this->scheduleDate)
            ->update([
            'schedule_status'     => 'completed',
        ]);
        } catch (\Throwable $e) {
            Log::error('[GenerateScheduleJob] failed: ' . $e->getMessage()
                . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
            $this->markFailed($e->getMessage());
            throw $e; // surface to the queue so it's recorded as a failed job too
        }
    }

    /**
     * Called by the queue if the job errors out or times out.
     */
    public function failed(\Throwable $e): void
    {
        $this->markFailed($e->getMessage());
    }

    private function markFailed(string $message): void
    {
        DB::table('schedule_runs')->where('id', $this->runId)->update([
            'status'      => 'failed',
            'finished_at' => now(),
            'message'     => $message,
            'updated_at'  => now(),
        ]);
        SelectedOrder::where('group_company_id', $this->companyId)
            ->where('user_id', $this->userId)
            ->whereDate('delivery_date', $this->scheduleDate)
            ->update([
            'schedule_status'     => 'failed',
        ]);
    }
}

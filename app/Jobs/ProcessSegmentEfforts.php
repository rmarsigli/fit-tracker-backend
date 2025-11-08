<?php declare(strict_types=1);

namespace App\Jobs;

use App\Models\Activity\Activity;
use App\Services\Segment\SegmentMatcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessSegmentEfforts implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Activity $activity,
        public float $minOverlapPercentage = 90.0
    ) {}

    public function handle(SegmentMatcherService $matcher): void
    {
        try {
            $efforts = $matcher->processActivity($this->activity, $this->minOverlapPercentage);

            Log::info('Segment efforts processed', [
                'activity_id' => $this->activity->id,
                'efforts_created' => $efforts->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process segment efforts', [
                'activity_id' => $this->activity->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessSegmentEfforts job failed', [
            'activity_id' => $this->activity->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

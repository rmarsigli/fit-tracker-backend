<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MetricsController extends Controller
{
    /**
     * Expose application metrics in Prometheus format
     * GET /metrics
     */
    public function index(): Response
    {
        $metrics = $this->collectMetrics();

        $output = $this->formatPrometheus($metrics);

        return response($output, 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }

    /**
     * Collect all application metrics
     */
    private function collectMetrics(): array
    {
        return [
            'api_requests_total' => $this->getApiRequestsCount(),
            'active_tracking_sessions' => $this->getActiveTrackingSessions(),
            'segment_detection_queue_depth' => $this->getQueueDepth('default'),
            'cache_hits_total' => $this->getCacheHits(),
            'cache_misses_total' => $this->getCacheMisses(),
            'database_connections_active' => $this->getActiveDatabaseConnections(),
        ];
    }

    /**
     * Format metrics in Prometheus format
     */
    private function formatPrometheus(array $metrics): string
    {
        $output = "# HELP fittrack_api_requests_total Total API requests\n";
        $output .= "# TYPE fittrack_api_requests_total counter\n";
        $output .= "fittrack_api_requests_total {$metrics['api_requests_total']}\n\n";

        $output .= "# HELP fittrack_active_tracking_sessions Active GPS tracking sessions\n";
        $output .= "# TYPE fittrack_active_tracking_sessions gauge\n";
        $output .= "fittrack_active_tracking_sessions {$metrics['active_tracking_sessions']}\n\n";

        $output .= "# HELP fittrack_segment_detection_queue_depth Pending segment detection jobs\n";
        $output .= "# TYPE fittrack_segment_detection_queue_depth gauge\n";
        $output .= "fittrack_segment_detection_queue_depth {$metrics['segment_detection_queue_depth']}\n\n";

        $output .= "# HELP fittrack_cache_hits_total Total cache hits\n";
        $output .= "# TYPE fittrack_cache_hits_total counter\n";
        $output .= "fittrack_cache_hits_total {$metrics['cache_hits_total']}\n\n";

        $output .= "# HELP fittrack_cache_misses_total Total cache misses\n";
        $output .= "# TYPE fittrack_cache_misses_total counter\n";
        $output .= "fittrack_cache_misses_total {$metrics['cache_misses_total']}\n\n";

        $output .= "# HELP fittrack_database_connections_active Active database connections\n";
        $output .= "# TYPE fittrack_database_connections_active gauge\n";
        $output .= "fittrack_database_connections_active {$metrics['database_connections_active']}\n\n";

        return $output;
    }

    /**
     * Get total API requests count
     */
    private function getApiRequestsCount(): int
    {
        return (int) Cache::get('metrics:api_requests_total', 0);
    }

    /**
     * Get active tracking sessions count (Redis keys matching 'tracking:*')
     */
    private function getActiveTrackingSessions(): int
    {
        try {
            $keys = Redis::connection()->keys('tracking:*');

            return count($keys);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get queue depth for a specific queue
     */
    private function getQueueDepth(string $queue): int
    {
        try {
            return DB::table('jobs')
                ->where('queue', $queue)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get cache hits count from Redis INFO
     */
    private function getCacheHits(): int
    {
        try {
            $info = Redis::connection()->info('stats');

            return (int) ($info['keyspace_hits'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get cache misses count from Redis INFO
     */
    private function getCacheMisses(): int
    {
        try {
            $info = Redis::connection()->info('stats');

            return (int) ($info['keyspace_misses'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get active database connections
     */
    private function getActiveDatabaseConnections(): int
    {
        try {
            $result = DB::selectOne('SELECT count(*) as count FROM pg_stat_activity WHERE state = ?', ['active']);

            return (int) $result->count;
        } catch (\Exception $e) {
            return 0;
        }
    }
}

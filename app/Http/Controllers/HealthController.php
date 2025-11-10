<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * Basic liveness probe - always returns 200 if app is running
     * GET /health
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Readiness probe - checks if app can serve traffic
     * GET /health/ready
     */
    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'sentry' => $this->checkSentry(),
        ];

        $allHealthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $allHealthy ? 'ready' : 'not_ready',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Detailed health information - admin only
     * GET /health/detailed
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'sentry' => $this->checkSentry(),
        ];

        $allHealthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'environment' => [
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
            'versions' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'postgres' => $this->getPostgresVersion(),
                'redis' => $this->getRedisVersion(),
            ],
            'timestamp' => now()->toIso8601String(),
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check Redis connectivity
     */
    private function checkRedis(): bool
    {
        try {
            Redis::connection()->ping();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check Sentry configuration
     */
    private function checkSentry(): bool
    {
        return ! empty(config('sentry.dsn'));
    }

    /**
     * Get PostgreSQL version
     */
    private function getPostgresVersion(): string
    {
        try {
            $result = DB::selectOne('SELECT version()');

            return $result ? (string) $result->version : 'unknown';
        } catch (\Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Get Redis version
     */
    private function getRedisVersion(): string
    {
        try {
            $info = Redis::connection()->info();

            return $info['redis_version'] ?? 'unknown';
        } catch (\Exception $e) {
            return 'unknown';
        }
    }
}

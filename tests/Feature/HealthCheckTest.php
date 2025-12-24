<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

it('returns ok status for basic health check', function () {
    $response = $this->getJson('/api/health');

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'timestamp',
        ])
        ->assertJson([
            'status' => 'ok',
        ]);
});

it('returns ready status when all services are healthy', function () {
    Redis::shouldReceive('connection->ping')->andReturn('PONG');
    DB::shouldReceive('connection->getPdo')->andReturn(true);

    $response = $this->getJson('/api/health/ready');

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'checks' => [
                'database',
                'redis',
                'sentry',
            ],
            'timestamp',
        ])
        ->assertJson([
            'status' => 'ready',
        ]);

    expect($response->json('checks.database'))->toBeTrue();
    expect($response->json('checks.redis'))->toBeTrue();
});

it('returns detailed health information', function () {
    Redis::shouldReceive('connection->ping')->andReturn('PONG');
    Redis::shouldReceive('connection->info')->andReturn(['redis_version' => '7.0.0']);
    DB::shouldReceive('connection->getPdo')->andReturn(true);
    DB::shouldReceive('selectOne')->andReturn((object) ['version' => 'PostgreSQL 16.0']);

    $response = $this->getJson('/api/health/detailed');

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'checks' => [
                'database',
                'redis',
                'sentry',
            ],
            'environment' => [
                'app_env',
                'app_debug',
                'php_version',
                'laravel_version',
            ],
            'versions' => [
                'php',
                'laravel',
                'postgres',
                'redis',
            ],
            'timestamp',
        ]);

    expect($response->json('environment.php_version'))->toBe(PHP_VERSION);
    expect($response->json('versions.php'))->toBe(PHP_VERSION);
});

<?php

declare(strict_types=1);

it('returns metrics in prometheus format', function () {
    $response = $this->getJson('/api/metrics');

    $response->assertOk();

    $content = $response->getContent();

    // Check Prometheus format headers
    expect($response->headers->get('Content-Type'))
        ->toContain('text/plain');

    // Check metric names present
    expect($content)->toContain('fittrack_api_requests_total');
    expect($content)->toContain('fittrack_active_tracking_sessions');
    expect($content)->toContain('fittrack_segment_detection_queue_depth');
    expect($content)->toContain('fittrack_cache_hits_total');
    expect($content)->toContain('fittrack_cache_misses_total');
    expect($content)->toContain('fittrack_database_connections_active');

    // Check Prometheus format (HELP and TYPE comments)
    expect($content)->toContain('# HELP');
    expect($content)->toContain('# TYPE');
});

it('metrics endpoint returns numeric values', function () {
    $response = $this->getJson('/api/metrics');

    $response->assertOk();

    $content = $response->getContent();

    // Extract metric values using regex
    preg_match('/fittrack_api_requests_total (\d+)/', $content, $matches);
    expect($matches[1] ?? null)->toBeNumeric();

    preg_match('/fittrack_active_tracking_sessions (\d+)/', $content, $matches);
    expect($matches[1] ?? null)->toBeNumeric();

    preg_match('/fittrack_database_connections_active (\d+)/', $content, $matches);
    expect($matches[1] ?? null)->toBeNumeric();
});

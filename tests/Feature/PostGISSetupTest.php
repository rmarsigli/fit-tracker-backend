<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('has postgis extension enabled', function () {
    $result = DB::select('SELECT PostGIS_Version()');

    expect($result)->not->toBeEmpty();
});

it('has spatial columns on users table', function () {
    $columns = DB::select("
        SELECT column_name, udt_name
        FROM information_schema.columns
        WHERE table_name = 'users'
        AND column_name = 'location'
    ");

    expect($columns)->toHaveCount(1);
});

it('has spatial columns on activities table', function () {
    $columns = DB::select("
        SELECT column_name, udt_name
        FROM information_schema.columns
        WHERE table_name = 'activities'
        AND column_name IN ('route', 'start_point', 'end_point')
    ");

    expect($columns)->toHaveCount(3);
});

it('has spatial columns on segments table', function () {
    $columns = DB::select("
        SELECT column_name, udt_name
        FROM information_schema.columns
        WHERE table_name = 'segments'
        AND column_name IN ('route', 'start_point', 'end_point')
    ");

    expect($columns)->toHaveCount(3);
});

it('has gist indexes on activities table', function () {
    $indexes = DB::select("
        SELECT indexname
        FROM pg_indexes
        WHERE tablename = 'activities'
        AND indexdef LIKE '%USING gist%'
    ");

    expect($indexes)->toHaveCount(3);
});

it('has gist indexes on segments table', function () {
    $indexes = DB::select("
        SELECT indexname
        FROM pg_indexes
        WHERE tablename = 'segments'
        AND indexdef LIKE '%USING gist%'
    ");

    expect($indexes)->toHaveCount(3);
});

it('has gist index on users table', function () {
    $indexes = DB::select("
        SELECT indexname
        FROM pg_indexes
        WHERE tablename = 'users'
        AND indexdef LIKE '%USING gist%'
    ");

    expect($indexes)->toHaveCount(1);
});

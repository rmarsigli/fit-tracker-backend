<?php declare(strict_types=1);

use App\Services\PostGIS\PostGISService;

beforeEach(function () {
    $this->service = app(PostGISService::class);
});

it('creates a valid POINT from coordinates', function () {
    $point = $this->service->makePoint(-23.5505, -46.6333);

    expect($point)->toContain('SRID=4326')
        ->toContain('POINT')
        ->toContain('-46.633')
        ->toContain('-23.550');
});

it('creates a valid POINT with altitude', function () {
    $point = $this->service->makePoint(-23.5505, -46.6333, 850.0);

    expect($point)->toContain('SRID=4326')
        ->toContain('POINT')
        ->toContain('-46.633')
        ->toContain('-23.550')
        ->toContain('850');
});

it('creates a valid LINESTRING from array of points', function () {
    $points = [
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6343],
        ['latitude' => -23.5525, 'longitude' => -46.6353],
    ];

    $lineString = $this->service->makeLineString($points);

    expect($lineString)->toContain('SRID=4326')
        ->toContain('LINESTRING')
        ->toContain('-46.633')
        ->toContain('-23.550');
});

it('creates a LINESTRING with altitude', function () {
    $points = [
        ['latitude' => -23.5505, 'longitude' => -46.6333, 'altitude' => 800.0],
        ['latitude' => -23.5515, 'longitude' => -46.6343, 'altitude' => 810.0],
    ];

    $lineString = $this->service->makeLineString($points);

    expect($lineString)->toContain('SRID=4326')
        ->toContain('LINESTRING')
        ->toContain('800')
        ->toContain('810');
});

it('throws exception for empty points array when creating LINESTRING', function () {
    expect(fn () => $this->service->makeLineString([]))
        ->toThrow(InvalidArgumentException::class, 'Points array cannot be empty');
});

it('throws exception for single point when creating LINESTRING', function () {
    $points = [
        ['latitude' => -23.5505, 'longitude' => -46.6333],
    ];

    expect(fn () => $this->service->makeLineString($points))
        ->toThrow(InvalidArgumentException::class, 'LineString requires at least 2 points');
});

it('throws exception for invalid point data', function () {
    $points = [
        ['latitude' => -23.5505], // missing longitude
        ['latitude' => -23.5515, 'longitude' => -46.6343],
    ];

    expect(fn () => $this->service->makeLineString($points))
        ->toThrow(InvalidArgumentException::class, 'Each point must have latitude and longitude');
});

it('calculates distance between two points', function () {
    // São Paulo to Rio de Janeiro (approximately 393km)
    $distance = $this->service->calculateDistance(
        -23.5505, -46.6333, // São Paulo
        -22.9068, -43.1729  // Rio de Janeiro
    );

    expect($distance)->toBeGreaterThan(390000) // > 390km
        ->toBeLessThan(400000); // < 400km
});

it('calculates distance for very close points', function () {
    // Two points approximately 100 meters apart
    $distance = $this->service->calculateDistance(
        -23.5505, -46.6333,
        -23.5515, -46.6333
    );

    expect($distance)->toBeGreaterThan(50) // > 50m
        ->toBeLessThan(200); // < 200m
});

it('calculates linestring distance', function () {
    $points = [
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6343],
        ['latitude' => -23.5525, 'longitude' => -46.6353],
    ];

    $lineString = $this->service->makeLineString($points);
    $distance = $this->service->calculateLineStringDistance($lineString);

    expect($distance)->toBeGreaterThan(0)
        ->toBeFloat();
});

it('detects intersection between two linestrings', function () {
    // Horizontal line
    $line1 = $this->service->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5505, 'longitude' => -46.6353],
    ]);

    // Vertical line (should intersect)
    $line2 = $this->service->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6343],
        ['latitude' => -23.5510, 'longitude' => -46.6343],
    ]);

    $intersects = $this->service->intersects($line1, $line2);

    expect($intersects)->toBeTrue();
});

it('detects no intersection between parallel linestrings', function () {
    // First horizontal line
    $line1 = $this->service->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5505, 'longitude' => -46.6353],
    ]);

    // Second horizontal line (parallel, no intersection)
    $line2 = $this->service->makeLineString([
        ['latitude' => -23.5515, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6353],
    ]);

    $intersects = $this->service->intersects($line1, $line2);

    expect($intersects)->toBeFalse();
});

it('gets intersection geometry between two linestrings', function () {
    $line1 = $this->service->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5505, 'longitude' => -46.6353],
    ]);

    $line2 = $this->service->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6343],
        ['latitude' => -23.5510, 'longitude' => -46.6343],
    ]);

    $intersection = $this->service->getIntersection($line1, $line2);

    expect($intersection)->not->toBeNull()
        ->toContain('POINT');
});

it('returns null for no intersection', function () {
    $line1 = $this->service->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5505, 'longitude' => -46.6343],
    ]);

    $line2 = $this->service->makeLineString([
        ['latitude' => -23.5515, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6343],
    ]);

    $intersection = $this->service->getIntersection($line1, $line2);

    expect($intersection)->toBeNull();
});

it('calculates geometry length', function () {
    $lineString = $this->service->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6343],
    ]);

    $length = $this->service->length($lineString);

    expect($length)->toBeGreaterThan(0)
        ->toBeFloat();
});

it('extracts coordinates from POINT', function () {
    $point = $this->service->makePoint(-23.5505, -46.6333);
    $coordinates = $this->service->extractPoint($point);

    expect($coordinates)->toHaveKey('latitude')
        ->toHaveKey('longitude')
        ->toHaveKey('altitude')
        ->and(round($coordinates['latitude'], 4))->toBe(-23.5505)
        ->and(round($coordinates['longitude'], 4))->toBe(-46.6333)
        ->and($coordinates['altitude'])->toBeNull();
});

it('extracts coordinates from POINT with altitude', function () {
    $point = $this->service->makePoint(-23.5505, -46.6333, 850.0);
    $coordinates = $this->service->extractPoint($point);

    expect(round($coordinates['altitude'], 1))->toBe(850.0);
});

it('extracts coordinates from LINESTRING', function () {
    $originalPoints = [
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6343],
        ['latitude' => -23.5525, 'longitude' => -46.6353],
    ];

    $lineString = $this->service->makeLineString($originalPoints);
    $extractedPoints = $this->service->extractLineString($lineString);

    expect($extractedPoints)->toHaveCount(3)
        ->and(round($extractedPoints[0]['latitude'], 4))->toBe(-23.5505)
        ->and(round($extractedPoints[0]['longitude'], 4))->toBe(-46.6333)
        ->and(round($extractedPoints[2]['latitude'], 4))->toBe(-23.5525);
});

it('creates buffer around a point', function () {
    $point = $this->service->makePoint(-23.5505, -46.6333);
    $buffered = $this->service->buffer($point, 1000.0); // 1km buffer

    expect($buffered)->toContain('SRID=4326')
        ->toContain('POLYGON');
});

it('checks if point is within distance from another point', function () {
    $point1 = $this->service->makePoint(-23.5505, -46.6333);
    $point2 = $this->service->makePoint(-23.5515, -46.6333); // ~111m away

    $withinDistance = $this->service->isWithinDistance($point1, $point2, 200.0); // 200m

    expect($withinDistance)->toBeTrue();
});

it('checks if point is not within distance', function () {
    $point1 = $this->service->makePoint(-23.5505, -46.6333);
    $point2 = $this->service->makePoint(-23.5515, -46.6333); // ~111m away

    $withinDistance = $this->service->isWithinDistance($point1, $point2, 50.0); // 50m

    expect($withinDistance)->toBeFalse();
});

it('gets centroid of a linestring', function () {
    $lineString = $this->service->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6343],
    ]);

    $centroid = $this->service->getCentroid($lineString);

    expect($centroid)->toContain('SRID=4326')
        ->toContain('POINT');
});

it('simplifies a complex linestring', function () {
    $points = [];
    for ($i = 0; $i < 100; $i++) {
        $points[] = [
            'latitude' => -23.5505 + ($i * 0.0001),
            'longitude' => -46.6333 + ($i * 0.0001),
        ];
    }

    $lineString = $this->service->makeLineString($points);
    $simplified = $this->service->simplify($lineString, 50.0); // 50m tolerance

    expect($simplified)->toContain('SRID=4326')
        ->toContain('LINESTRING');

    $originalCount = count($this->service->extractLineString($lineString));
    $simplifiedCount = count($this->service->extractLineString($simplified));

    expect($simplifiedCount)->toBeLessThan($originalCount);
});

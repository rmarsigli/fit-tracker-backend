<?php

declare(strict_types=1);

namespace App\Services\PostGIS;

use Illuminate\Support\Facades\DB;

class PostGISService
{
    /**
     * Convert a point (latitude, longitude) to PostGIS POINT format
     */
    public function makePoint(float $latitude, float $longitude, ?float $altitude = null): string
    {
        if ($altitude !== null) {
            return sprintf('SRID=4326;POINT(%f %f %f)', $longitude, $latitude, $altitude);
        }

        return sprintf('SRID=4326;POINT(%f %f)', $longitude, $latitude);
    }

    /**
     * Convert an array of points to PostGIS LINESTRING format
     *
     * @param  array<int, array{latitude: float, longitude: float, altitude?: float}>  $points
     */
    public function makeLineString(array $points): string
    {
        if (empty($points)) {
            throw new \InvalidArgumentException('Points array cannot be empty');
        }

        if (count($points) < 2) {
            throw new \InvalidArgumentException('LineString requires at least 2 points');
        }

        $hasAltitude = isset($points[0]['altitude']);
        $coordinates = [];

        foreach ($points as $point) {
            if (! isset($point['latitude'], $point['longitude'])) {
                throw new \InvalidArgumentException('Each point must have latitude and longitude');
            }

            if ($hasAltitude) {
                $altitude = $point['altitude'] ?? 0.0;
                $coordinates[] = sprintf('%f %f %f', $point['longitude'], $point['latitude'], $altitude);
            } else {
                $coordinates[] = sprintf('%f %f', $point['longitude'], $point['latitude']);
            }
        }

        $coordinateString = implode(',', $coordinates);

        return "SRID=4326;LINESTRING({$coordinateString})";
    }

    /**
     * Calculate distance between two points using PostGIS ST_Distance
     * Returns distance in meters
     */
    public function calculateDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $point1 = $this->makePoint($lat1, $lng1);
        $point2 = $this->makePoint($lat2, $lng2);

        $result = DB::selectOne(
            'SELECT ST_Distance(
                ST_Transform(ST_GeomFromEWKT(?), 3857),
                ST_Transform(ST_GeomFromEWKT(?), 3857)
            ) as distance',
            [$point1, $point2]
        );

        return (float) $result->distance;
    }

    /**
     * Calculate distance along a linestring using PostGIS ST_Length
     * Returns distance in meters
     */
    public function calculateLineStringDistance(string $lineString): float
    {
        $result = DB::selectOne(
            'SELECT ST_Length(
                ST_Transform(ST_GeomFromEWKT(?), 3857)
            ) as distance',
            [$lineString]
        );

        return (float) $result->distance;
    }

    /**
     * Calculate the intersection between two geometries
     */
    public function getIntersection(string $geometry1, string $geometry2): ?string
    {
        $result = DB::selectOne(
            'SELECT ST_AsEWKT(ST_Intersection(
                ST_GeomFromEWKT(?),
                ST_GeomFromEWKT(?)
            )) as intersection',
            [$geometry1, $geometry2]
        );

        $intersection = $result->intersection !== null ? (string) $result->intersection : null;

        if ($intersection && str_contains($intersection, 'EMPTY')) {
            return null;
        }

        return $intersection;
    }

    public function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if two geometries intersect
     */
    public function intersects(string $geometry1, string $geometry2): bool
    {
        $result = DB::selectOne(
            'SELECT ST_Intersects(
                ST_GeomFromEWKT(?),
                ST_GeomFromEWKT(?)
            ) as intersects',
            [$geometry1, $geometry2]
        );

        return (bool) $result->intersects;
    }

    /**
     * Calculate the length of a geometry in meters
     */
    public function length(string $geometry): float
    {
        $result = DB::selectOne(
            'SELECT ST_Length(
                ST_Transform(ST_GeomFromEWKT(?), 3857)
            ) as length',
            [$geometry]
        );

        return (float) $result->length;
    }

    /**
     * Extract coordinates from a POINT geometry
     *
     * @return array{latitude: float, longitude: float, altitude: ?float}
     */
    public function extractPoint(string $point): array
    {
        $result = DB::selectOne(
            'SELECT
                ST_Y(ST_GeomFromEWKT(?)) as latitude,
                ST_X(ST_GeomFromEWKT(?)) as longitude,
                ST_Z(ST_GeomFromEWKT(?)) as altitude',
            [$point, $point, $point]
        );

        return [
            'latitude' => (float) $result->latitude,
            'longitude' => (float) $result->longitude,
            'altitude' => $result->altitude !== null ? (float) $result->altitude : null,
        ];
    }

    /**
     * Extract coordinates from a LINESTRING geometry
     *
     * @return array<int, array{latitude: float, longitude: float, altitude: ?float}>
     */
    public function extractLineString(string $lineString): array
    {
        $result = DB::select(
            'SELECT
                ST_Y(geom) as latitude,
                ST_X(geom) as longitude,
                ST_Z(geom) as altitude
            FROM ST_DumpPoints(ST_GeomFromEWKT(?))
            ORDER BY path',
            [$lineString]
        );

        return array_map(function ($point) {
            return [
                'latitude' => (float) $point->latitude,
                'longitude' => (float) $point->longitude,
                'altitude' => $point->altitude !== null ? (float) $point->altitude : null,
            ];
        }, $result);
    }

    /**
     * Create a buffer around a geometry (in meters)
     */
    public function buffer(string $geometry, float $radiusMeters): string
    {
        $result = DB::selectOne(
            'SELECT ST_AsEWKT(
                ST_Transform(
                    ST_Buffer(
                        ST_Transform(ST_GeomFromEWKT(?), 3857),
                        ?
                    ),
                    4326
                )
            ) as buffered',
            [$geometry, $radiusMeters]
        );

        return (string) $result->buffered;
    }

    /**
     * Check if a point is within a distance from another geometry
     */
    public function isWithinDistance(
        string $geometry1,
        string $geometry2,
        float $distanceMeters
    ): bool {
        $result = DB::selectOne(
            'SELECT ST_DWithin(
                ST_Transform(ST_GeomFromEWKT(?), 3857),
                ST_Transform(ST_GeomFromEWKT(?), 3857),
                ?
            ) as within_distance',
            [$geometry1, $geometry2, $distanceMeters]
        );

        return (bool) $result->within_distance;
    }

    /**
     * Get the centroid of a geometry
     */
    public function getCentroid(string $geometry): string
    {
        $result = DB::selectOne(
            'SELECT ST_AsEWKT(ST_Centroid(ST_GeomFromEWKT(?))) as centroid',
            [$geometry]
        );

        return (string) $result->centroid;
    }

    /**
     * Simplify a geometry (reduce number of points while preserving shape)
     * Tolerance is in meters
     */
    public function simplify(string $geometry, float $toleranceMeters = 10.0): string
    {
        $result = DB::selectOne(
            'SELECT ST_AsEWKT(
                ST_Transform(
                    ST_Simplify(
                        ST_Transform(ST_GeomFromEWKT(?), 3857),
                        ?
                    ),
                    4326
                )
            ) as simplified',
            [$geometry, $toleranceMeters]
        );

        return (string) $result->simplified;
    }
}

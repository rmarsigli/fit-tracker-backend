<?php declare(strict_types=1);

namespace App\Services\PostGIS;

use App\Models\Activity\Activity;
use App\Models\Segment\Segment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GeoQueryService
{
    /**
     * Allowed geometry column names for security
     */
    private const ALLOWED_GEOMETRY_COLUMNS = [
        'route',
        'location',
        'start_point',
        'end_point',
    ];

    /**
     * Allowed SQL directions for security
     */
    private const ALLOWED_DIRECTIONS = ['ASC', 'DESC'];

    public function __construct(
        protected PostGISService $postGIS
    ) {}

    /**
     * Validate column name to prevent SQL injection
     */
    private function validateColumnName(string $columnName): void
    {
        if (! in_array($columnName, self::ALLOWED_GEOMETRY_COLUMNS, true)) {
            throw new \InvalidArgumentException(
                "Invalid geometry column name: {$columnName}. Allowed: ".implode(', ', self::ALLOWED_GEOMETRY_COLUMNS)
            );
        }
    }

    /**
     * Validate SQL direction to prevent SQL injection
     */
    private function validateDirection(string $direction): void
    {
        $direction = strtoupper($direction);
        if (! in_array($direction, self::ALLOWED_DIRECTIONS, true)) {
            throw new \InvalidArgumentException(
                "Invalid SQL direction: {$direction}. Allowed: ".implode(', ', self::ALLOWED_DIRECTIONS)
            );
        }
    }

    /**
     * Find activities near a point within a given radius (in meters)
     *
     * @return Collection<int, Activity>
     */
    public function findActivitiesNearPoint(
        float $latitude,
        float $longitude,
        float $radiusMeters = 5000.0,
        ?int $limit = 20
    ): Collection {
        $point = $this->postGIS->makePoint($latitude, $longitude);

        return Activity::query()
            ->with('user')
            ->whereNotNull('route')
            ->whereRaw(
                'ST_DWithin(
                    ST_Transform(route::geometry, 3857),
                    ST_Transform(ST_GeomFromEWKT(?), 3857),
                    ?
                )',
                [$point, $radiusMeters]
            )
            ->orderByRaw(
                'ST_Distance(
                    ST_Transform(route::geometry, 3857),
                    ST_Transform(ST_GeomFromEWKT(?), 3857)
                ) ASC',
                [$point]
            )
            ->limit($limit)
            ->get();
    }

    /**
     * Find segments near a point within a given radius (in meters)
     *
     * @return Collection<int, Segment>
     */
    public function findSegmentsNearPoint(
        float $latitude,
        float $longitude,
        float $radiusMeters = 5000.0,
        ?int $limit = 20
    ): Collection {
        $point = $this->postGIS->makePoint($latitude, $longitude);

        return Segment::query()
            ->whereNotNull('route')
            ->whereRaw(
                'ST_DWithin(
                    ST_Transform(route::geometry, 3857),
                    ST_Transform(ST_GeomFromEWKT(?), 3857),
                    ?
                )',
                [$point, $radiusMeters]
            )
            ->orderByRaw(
                'ST_Distance(
                    ST_Transform(route::geometry, 3857),
                    ST_Transform(ST_GeomFromEWKT(?), 3857)
                ) ASC',
                [$point]
            )
            ->limit($limit)
            ->get();
    }

    /**
     * Find users near a point within a given radius (in meters)
     *
     * @return Collection<int, User>
     */
    public function findUsersNearPoint(
        float $latitude,
        float $longitude,
        float $radiusMeters = 10000.0,
        ?int $limit = 50
    ): Collection {
        $point = $this->postGIS->makePoint($latitude, $longitude);

        return User::query()
            ->whereNotNull('location')
            ->whereRaw(
                'ST_DWithin(
                    ST_Transform(location::geometry, 3857),
                    ST_Transform(ST_GeomFromEWKT(?), 3857),
                    ?
                )',
                [$point, $radiusMeters]
            )
            ->orderByRaw(
                'ST_Distance(
                    ST_Transform(location::geometry, 3857),
                    ST_Transform(ST_GeomFromEWKT(?), 3857)
                ) ASC',
                [$point]
            )
            ->limit($limit)
            ->get();
    }

    /**
     * Find segments that intersect with an activity route
     *
     * @return Collection<int, array{segment: Segment, overlap_percentage: float, overlap_distance: float}>
     */
    public function findIntersectingSegments(Activity $activity, float $minOverlapPercentage = 90.0): Collection
    {
        if (! $activity->route) {
            return collect();
        }

        $segments = Segment::query()
            ->whereNotNull('route')
            ->whereRaw(
                'ST_Intersects(route::geometry, ST_GeomFromEWKT(?))',
                [$activity->route]
            )
            ->get();

        return $segments->map(function (Segment $segment) use ($activity, $minOverlapPercentage) {
            $intersection = $this->postGIS->getIntersection($activity->route, $segment->route);

            if (! $intersection) {
                return null;
            }

            $intersectionLength = $this->postGIS->length($intersection);
            $segmentLength = $this->postGIS->length($segment->route);

            $overlapPercentage = $segmentLength > 0
                ? ($intersectionLength / $segmentLength) * 100
                : 0;

            if ($overlapPercentage < $minOverlapPercentage) {
                return null;
            }

            return [
                'segment' => $segment,
                'overlap_percentage' => round($overlapPercentage, 2),
                'overlap_distance' => round($intersectionLength, 2),
            ];
        })->filter()->values();
    }

    /**
     * Find activities that intersect with a segment
     *
     * @return Collection<int, Activity>
     */
    public function findActivitiesIntersectingSegment(Segment $segment, ?int $limit = 100): Collection
    {
        if (! $segment->route) {
            return collect();
        }

        return Activity::query()
            ->whereNotNull('route')
            ->whereRaw(
                'ST_Intersects(route::geometry, ST_GeomFromEWKT(?))',
                [$segment->route]
            )
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate distance between two activities' routes
     */
    public function calculateDistanceBetweenActivities(Activity $activity1, Activity $activity2): ?float
    {
        if (! $activity1->start_point || ! $activity2->start_point) {
            return null;
        }

        $point1 = $this->postGIS->extractPoint($activity1->start_point);
        $point2 = $this->postGIS->extractPoint($activity2->start_point);

        return $this->postGIS->calculateDistance(
            $point1['latitude'],
            $point1['longitude'],
            $point2['latitude'],
            $point2['longitude']
        );
    }

    /**
     * Find activities within a bounding box
     *
     * @return Collection<int, Activity>
     */
    public function findActivitiesInBoundingBox(
        float $minLat,
        float $minLng,
        float $maxLat,
        float $maxLng,
        ?int $limit = 100
    ): Collection {
        return Activity::query()
            ->whereNotNull('route')
            ->whereRaw(
                'route::geometry && ST_MakeEnvelope(?, ?, ?, ?, 4326)',
                [$minLng, $minLat, $maxLng, $maxLat]
            )
            ->limit($limit)
            ->get();
    }

    /**
     * Find segments within a bounding box
     *
     * @return Collection<int, Segment>
     */
    public function findSegmentsInBoundingBox(
        float $minLat,
        float $minLng,
        float $maxLat,
        float $maxLng,
        ?int $limit = 100
    ): Collection {
        return Segment::query()
            ->whereNotNull('route')
            ->whereRaw(
                'route::geometry && ST_MakeEnvelope(?, ?, ?, ?, 4326)',
                [$minLng, $minLat, $maxLng, $maxLat]
            )
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities sorted by proximity to a given activity
     *
     * @return Collection<int, Activity>
     */
    public function findSimilarRouteActivities(
        Activity $activity,
        float $maxDistanceMeters = 1000.0,
        ?int $limit = 20
    ): Collection {
        if (! $activity->route) {
            return collect();
        }

        return Activity::query()
            ->where('id', '!=', $activity->id)
            ->whereNotNull('route')
            ->whereRaw(
                'ST_DWithin(
                    ST_Transform(route::geometry, 3857),
                    ST_Transform(ST_GeomFromEWKT(?), 3857),
                    ?
                )',
                [$activity->route, $maxDistanceMeters]
            )
            ->orderByRaw(
                'ST_Distance(
                    ST_Transform(route::geometry, 3857),
                    ST_Transform(ST_GeomFromEWKT(?), 3857)
                ) ASC',
                [$activity->route]
            )
            ->limit($limit)
            ->get();
    }

    /**
     * Apply nearby filter to a query builder
     */
    public function scopeNearPoint(
        Builder $query,
        string $columnName,
        float $latitude,
        float $longitude,
        float $radiusMeters
    ): Builder {
        $this->validateColumnName($columnName);
        $point = $this->postGIS->makePoint($latitude, $longitude);

        return $query->whereRaw(
            "ST_DWithin(
                ST_Transform({$columnName}::geometry, 3857),
                ST_Transform(ST_GeomFromEWKT(?), 3857),
                ?
            )",
            [$point, $radiusMeters]
        );
    }

    /**
     * Apply distance ordering to a query builder
     */
    public function scopeOrderByDistance(
        Builder $query,
        string $columnName,
        float $latitude,
        float $longitude,
        string $direction = 'ASC'
    ): Builder {
        $this->validateColumnName($columnName);
        $this->validateDirection($direction);
        $point = $this->postGIS->makePoint($latitude, $longitude);

        return $query->orderByRaw(
            "ST_Distance(
                ST_Transform({$columnName}::geometry, 3857),
                ST_Transform(ST_GeomFromEWKT(?), 3857)
            ) {$direction}",
            [$point]
        );
    }
}

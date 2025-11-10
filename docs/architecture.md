# FitTrack Backend - Architecture Documentation

> System design and technical decisions for FitTrack fitness tracking platform.

**Target audience**: Developers who want to understand how the system works (not just what it does).

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Core Concepts](#core-concepts)
3. [Data Flow](#data-flow)
4. [PostGIS Integration](#postgis-integration)
5. [Background Processing](#background-processing)
6. [Security & Authentication](#security--authentication)
7. [Performance Optimizations](#performance-optimizations)
8. [Monitoring & Observability](#monitoring--observability)

---

## System Overview

FitTrack is a fitness tracking platform (think Strava for Brazil) built with Laravel 12, PostgreSQL + PostGIS, and Redis.

### High-Level Architecture

```
┌──────────────┐
│   Client     │  (Mobile App, Web App)
│   (React)    │
└──────┬───────┘
       │ HTTPS (JSON)
       ▼
┌──────────────────────────────────────┐
│        Laravel 12 API (PHP 8.4)      │
│  ┌────────────┐  ┌────────────┐      │
│  │Controllers │  │  Services  │      │
│  └─────┬──────┘  └─────┬──────┘      │
│        │                │             │
│        ▼                ▼             │
│  ┌────────────┐  ┌────────────┐      │
│  │   Models   │  │ ValueObjects│      │
│  └─────┬──────┘  └────────────┘      │
└────────┼───────────────────────────

───┘
         │
         ▼
┌─────────────────────────────────────┐
│  PostgreSQL 16 + PostGIS 3.4        │
│  - Activities, Segments, Users      │
│  - Spatial indexes (GIST)           │
│  - Geometry: LINESTRING, POINT      │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│  Redis 7.2                          │
│  - GPS tracking (TTL: 2 hours)      │
│  - Cache (5 min for feeds)          │
│  - Queue jobs                       │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│  External Services                  │
│  - Sentry (error tracking)          │
│  - Prometheus (metrics)             │
└─────────────────────────────────────┘
```

### Technology Stack

| Component | Technology | Why? |
|-----------|-----------|------|
| **Backend** | Laravel 12 (PHP 8.4) | Modern PHP framework, excellent ecosystem |
| **Database** | PostgreSQL 16 | ACID compliance, powerful JSON support |
| **Geospatial** | PostGIS 3.4 | Native geospatial queries (faster than packages) |
| **Cache/Queue** | Redis 7.2 | Fast in-memory storage, pub/sub support |
| **Authentication** | Laravel Sanctum | Simple token-based auth for SPAs |
| **Testing** | Pest 4 | Modern testing framework, browser testing |
| **Static Analysis** | PHPStan Level 5 | Type safety, zero errors |
| **Code Style** | Laravel Pint | PSR-12 compliance |
| **Error Tracking** | Sentry | Production error monitoring |

---

## Core Concepts

### 1. Activities

**What**: A completed workout (run, ride, walk, swim, gym, other)

**Attributes**:
- Distance, duration, elevation gain/loss
- GPS route (stored as PostGIS `LINESTRING`)
- Heart rate, speed, cadence
- Visibility (public, followers, private)

**Lifecycle**:
1. **Manual creation**: User logs past activity via API
2. **Real-time tracking**: User starts GPS tracking → sends points → finishes
3. **Post-processing**: Segment detection, statistics calculation

**Storage**:
- **During tracking**: Redis (2-hour TTL)
- **After finish**: PostgreSQL (permanent)

### 2. Segments

**What**: Specific portions of routes where athletes compete for fastest time (like Strava KOMs)

**How they work**:
1. Someone creates a segment (e.g., "Ibirapuera Loop 3.8km")
2. When you finish an activity, system checks if your route overlaps ≥90% with any segment
3. If match found → creates `SegmentEffort` record
4. Updates leaderboard automatically

**KOM/QOM**:
- **KOM** (King of Mountain): Fastest male athlete on segment
- **QOM** (Queen of Mountain): Fastest female athlete on segment
- **PR** (Personal Record): Your best time on segment

**Storage**: PostgreSQL with spatial index on `start_point` and `end_point`

### 3. GPS Tracking

**What**: Real-time location tracking during activity

**Flow**:
```
Client                Server (Redis)           Server (PostgreSQL)
  │                         │                          │
  ├─ Start Tracking ───────►│                          │
  │                         │ Create key (2h TTL)     │
  │                         │                          │
  ├─ Send GPS Point ────────►│                          │
  │  (every 5-10s)          │ Append to list           │
  ├─ Send GPS Point ────────►│                          │
  ├─ Send GPS Point ────────►│                          │
  │                         │                          │
  ├─ Finish ────────────────►│──────────────────────────►│
  │                         │ Move points to DB        │
  │                         │ Delete Redis key         │
  │                         │ Dispatch job             │
```

**Why Redis?**
- Fast writes (thousands of GPS points)
- Automatic expiration (no cleanup needed)
- Low overhead compared to PostgreSQL

### 4. Challenges

**What**: Time-based competitions (distance, duration, elevation)

**Types**:
- **Distance**: Total distance in timeframe (e.g., "100km in November")
- **Duration**: Total moving time (e.g., "10 hours in a month")
- **Elevation**: Total elevation gain (e.g., "Climb 5000m this week")

**Progress tracking**: Automatic via `ChallengeService` when activity is saved

### 5. Social Features

**What**: Follow users, like activities, comment, feeds

**Feeds**:
- **Following feed**: Activities from users you follow (cached 5 min)
- **Nearby feed**: Activities near location using PostGIS `ST_DWithin` (cached 5 min)
- **Trending feed**: Most liked activities in timeframe (cached 5 min)

---

## Data Flow

### Real-Time GPS Tracking Flow

```
1. User starts tracking
   ┌────────────────────────────────────────┐
   │ POST /tracking/start                   │
   │ { type: "run", title: "Morning Run" } │
   └─────────────┬──────────────────────────┘
                 ▼
   ┌────────────────────────────────────────┐
   │ ActivityTrackingService::startTracking │
   │ - Generate tracking_id                 │
   │ - Store in Redis (2h TTL)              │
   │ - Return tracking_id to client         │
   └─────────────┬──────────────────────────┘
                 ▼
   ┌────────────────────────────────────────┐
   │ Client receives tracking_id            │
   │ Starts sending GPS points              │
   └────────────────────────────────────────┘

2. User sends GPS points (every 5-10 seconds)
   ┌────────────────────────────────────────┐
   │ POST /tracking/{id}/track              │
   │ { lat: -23.5505, lng: -46.6333 }      │
   └─────────────┬──────────────────────────┘
                 ▼
   ┌────────────────────────────────────────┐
   │ ActivityTrackingService::trackLocation │
   │ - Validate GPS point                   │
   │ - Calculate distance (Haversine)       │
   │ - Calculate elevation gain/loss        │
   │ - Append to Redis list                 │
   └─────────────┬──────────────────────────┘
                 ▼
   ┌────────────────────────────────────────┐
   │ Return current stats                   │
   │ { distance: 2345.67, duration: 720 }  │
   └────────────────────────────────────────┘

3. User finishes tracking
   ┌────────────────────────────────────────┐
   │ POST /tracking/{id}/finish             │
   └─────────────┬──────────────────────────┘
                 ▼
   ┌────────────────────────────────────────┐
   │ ActivityTrackingService::finish        │
   │ - Get all GPS points from Redis        │
   │ - Calculate final metrics              │
   │ - Create Activity in PostgreSQL        │
   │ - Store route as PostGIS LINESTRING    │
   │ - Delete Redis key                     │
   │ - Dispatch ProcessSegmentEfforts job   │
   └─────────────┬──────────────────────────┘
                 ▼
   ┌────────────────────────────────────────┐
   │ Return Activity object                 │
   └────────────────────────────────────────┘
```

### Segment Detection Flow (Background Job)

```
1. Activity finished → Job dispatched
   ┌────────────────────────────────────────┐
   │ ProcessSegmentEfforts job queued       │
   └─────────────┬──────────────────────────┘
                 ▼
   ┌────────────────────────────────────────┐
   │ Get activity route (LINESTRING)        │
   │ Get all segments of same type          │
   └─────────────┬──────────────────────────┘
                 ▼
2. Check overlap for each segment
   ┌────────────────────────────────────────┐
   │ PostGIS: ST_Intersection(route, seg)   │
   │ Calculate overlap percentage           │
   └─────────────┬──────────────────────────┘
                 ▼
   ┌────────────────────────────────────────┐
   │ If overlap ≥ 90%:                      │
   │ - Extract segment portion from route   │
   │ - Calculate elapsed time               │
   │ - Calculate average speed              │
   │ - Create SegmentEffort record          │
   └─────────────┬──────────────────────────┘
                 ▼
3. Update leaderboards
   ┌────────────────────────────────────────┐
   │ SegmentMatcherService::updateLeaderboard│
   │ - Get all efforts for segment          │
   │ - Order by elapsed_time ASC            │
   │ - Mark fastest male as KOM             │
   │ - Mark fastest female as QOM           │
   │ - Mark user's best as PR               │
   └─────────────┬──────────────────────────┘
                 ▼
   ┌────────────────────────────────────────┐
   │ Done! User can now check:              │
   │ - GET /me/records (personal records)   │
   │ - GET /segments/{id}/leaderboard       │
   └────────────────────────────────────────┘
```

### Social Feed Generation

```
1. Following Feed
   ┌────────────────────────────────────────┐
   │ GET /feed/following?limit=20           │
   └─────────────┬──────────────────────────┘
                 ▼
   ┌────────────────────────────────────────┐
   │ FeedService::getFollowingFeed          │
   │ - Check cache (key: feed:user:{id})    │
   │ - If miss: Query DB for activities     │
   │   from followed users                  │
   │ - Cache for 5 minutes                  │
   │ - Return activities                    │
   └────────────────────────────────────────┘

2. Nearby Feed (using PostGIS)
   ┌────────────────────────────────────────┐
   │ GET /feed/nearby?lat=-23&lng=-46       │
   └─────────────┬──────────────────────────┘
                 ▼
   ┌────────────────────────────────────────┐
   │ FeedService::getNearbyFeed             │
   │ - Create POINT from lat/lng            │
   │ - Query: ST_DWithin(route, point, 10km)│
   │ - Cache for 5 minutes                  │
   │ - Return activities                    │
   └────────────────────────────────────────┘
```

---

## PostGIS Integration

### Why PostGIS?

We use **native PostgreSQL + PostGIS** instead of PHP packages because:

1. **Performance**: PostGIS queries are 10-100x faster than PHP calculations
2. **Spatial indexes**: GIST indexes make geospatial queries extremely fast (< 1ms)
3. **Accuracy**: PostGIS uses geodetic calculations (accounts for Earth's curvature)
4. **Features**: ST_Intersection, ST_Distance, ST_DWithin, etc. all built-in

### Key PostGIS Functions Used

#### ST_GeomFromEWKT - Create geometry from text

```sql
SELECT ST_GeomFromEWKT('SRID=4326;POINT(-46.6333 -23.5505)');
```

**Use case**: Convert lat/lng to PostGIS POINT

#### ST_MakeLine - Create LINESTRING from points

```sql
SELECT ST_MakeLine(ARRAY[
  ST_MakePoint(-46.6333, -23.5505),
  ST_MakePoint(-46.6340, -23.5510),
  ST_MakePoint(-46.6350, -23.5515)
]);
```

**Use case**: Convert GPS points array to activity route

#### ST_Intersection - Find overlap between geometries

```sql
SELECT ST_Intersection(activity_route, segment_route);
```

**Use case**: Check if activity route matches segment

#### ST_Distance - Calculate distance between geometries

```sql
-- Distance in meters (requires projection transform)
SELECT ST_Distance(
  ST_Transform(point1, 3857),
  ST_Transform(point2, 3857)
) as distance_meters;
```

**Use case**: Calculate distance between two points or nearest segment

**SRID Explanation**:
- **4326** (WGS84): Latitude/longitude (degrees) - used for storage
- **3857** (Web Mercator): Projected coordinates (meters) - used for distance calculations

#### ST_DWithin - Find geometries within radius

```sql
SELECT *
FROM activities
WHERE ST_DWithin(
  route,
  ST_GeomFromEWKT('SRID=4326;POINT(-46.6333 -23.5505)'),
  0.1  -- degrees (~11km at equator)
);
```

**Use case**: Find activities near a location (nearby feed)

### Spatial Indexes

We use **GIST indexes** for fast geospatial queries:

```sql
-- From migration
CREATE INDEX activities_route_gist ON activities USING GIST (route);
CREATE INDEX segments_start_point_gist ON segments USING GIST (start_point);
CREATE INDEX segments_end_point_gist ON segments USING GIST (end_point);
```

**Performance impact**:
- Without index: ~500ms for nearby query
- With GIST index: < 1ms for nearby query

**Total spatial indexes**: 7 (activities, segments, segment_efforts)

### PostGIS Performance Tips

1. **Always use spatial indexes**: Create GIST index on geometry columns
2. **Transform for distance**: Use SRID 3857 for accurate meter calculations
3. **Use ST_DWithin instead of ST_Distance**: ST_DWithin uses index, ST_Distance doesn't
4. **Simplify geometries when possible**: Use ST_Simplify for large routes

---

## Background Processing

### Laravel Queues

We use **Redis** as queue driver for asynchronous processing.

**Queue Configuration** (`.env`):
```env
QUEUE_CONNECTION=redis
```

**Queue Jobs**:

#### ProcessSegmentEfforts

**Triggered by**: Activity finished (POST `/tracking/{id}/finish`)

**What it does**:
1. Gets activity route from PostgreSQL
2. Finds all segments of same type (run/ride)
3. For each segment:
   - Check route overlap using PostGIS `ST_Intersection`
   - If overlap ≥ 90%: Create `SegmentEffort` record
   - Calculate elapsed time, speed, rank
4. Update leaderboards (mark KOM/QOM/PR)

**Execution time**: Usually < 2 seconds (depends on number of segments)

**Retry logic**: 3 attempts with exponential backoff (1s, 5s, 15s)

**Failure handling**: Logged to Sentry, can be retried manually

**Code**: `app/Jobs/ProcessSegmentEfforts.php`

### Queue Workers

**Development**: Run manually in separate terminal
```bash
php artisan queue:work
```

**Production**: Use Supervisor to keep workers running

**Supervisor config** (`/etc/supervisor/conf.d/fittrack-worker.conf`):
```ini
[program:fittrack-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/fittrack/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/fittrack/storage/logs/worker.log
stopwaitsecs=3600
```

**Monitor queue**:
```bash
# Check queue size
php artisan queue:monitor redis

# Check failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}

# Clear failed jobs
php artisan queue:flush
```

---

## Security & Authentication

### Laravel Sanctum

We use **Laravel Sanctum** for token-based authentication.

**How it works**:
1. User registers/logs in → receives API token
2. Client stores token (localStorage, secure storage, etc)
3. Client includes token in all requests: `Authorization: Bearer {token}`
4. Laravel validates token via Sanctum middleware

**Token Storage**: `personal_access_tokens` table (PostgreSQL)

**Token Lifetime**: Never expires (until revoked)

**Token Abilities**: Not used (all tokens have full access)

### Middleware

**API routes** (`routes/api.php`):
```php
// Public routes (no auth)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes (require auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('activities', ActivityController::class);
    Route::apiResource('segments', SegmentController::class);
    // ...
});
```

### Authorization

**Model Policies**: Not used (activities/segments belong to users, checked in controller)

**Controller authorization**:
```php
public function update(UpdateActivityData $data, Activity $activity): JsonResponse
{
    // Check ownership
    if ($activity->user_id !== auth()->id()) {
        abort(403, 'Unauthorized');
    }

    // Update activity...
}
```

### Rate Limiting

**Configured in** `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->throttleApi('60,1');  // 60 requests per minute
})
```

**Different limits per route** (if needed):
```php
Route::middleware('throttle:5,1')->group(function () {
    // Auth endpoints: 5 requests/minute
    Route::post('/auth/register', ...);
    Route::post('/auth/login', ...);
});

Route::middleware('throttle:20,1')->group(function () {
    // Tracking endpoints: 20 requests/minute
    Route::post('/tracking/{id}/track', ...);
});
```

### Security Best Practices

1. **Input validation**: All requests validated via Data classes (Spatie Laravel Data)
2. **SQL injection**: Protected by Eloquent ORM + parameterized queries
3. **XSS**: Not applicable (API only, no HTML rendering)
4. **CSRF**: Not applicable (stateless API with token auth)
5. **Dependency scanning**: `composer audit` runs in CI
6. **Error details**: Hidden in production (via Sentry)

---

## Performance Optimizations

### Database Query Optimization

#### Prevent N+1 Queries

**Bad** (N+1 query):
```php
$activities = Activity::all();
foreach ($activities as $activity) {
    echo $activity->user->name;  // N+1!
}
```

**Good** (eager loading):
```php
$activities = Activity::with('user')->get();
foreach ($activities as $activity) {
    echo $activity->user->name;  // Single query!
}
```

**Laravel Telescope** helps identify N+1 queries in development.

#### Database Indexes

**Total indexes**: 35 (including spatial indexes)

**Key indexes**:
- Primary keys (auto-created)
- Foreign keys: `activities(user_id)`, `segment_efforts(segment_id, user_id)`
- Unique constraints: `users(email)`, `users(username)`
- Spatial indexes: 7 GIST indexes on geometry columns
- Composite indexes: `segment_efforts(segment_id, user_id, is_pr)`

**Index usage**: Check with `EXPLAIN ANALYZE` in PostgreSQL

#### Limit Eager Loading

**Laravel 12** allows limiting eagerly loaded records:

```php
Activity::with([
    'likes' => fn($q) => $q->limit(10),
    'comments' => fn($q) => $q->latest()->limit(5)
])->get();
```

**Use case**: Activity feed with preview of likes/comments

### Redis Caching

**Cache Strategy**: Cache feeds for 5 minutes to reduce DB load

**Following Feed**:
```php
Cache::remember("feed:user:{$userId}", 300, function () use ($userId) {
    return FeedService::getFollowingFeed($userId);
});
```

**Cache Invalidation**:
- Time-based (5 min TTL)
- Manual invalidation not needed (feeds update frequently)

**Cache Driver**: Redis (configured in `.env`)

### PostGIS Spatial Indexes

**All geometry columns have GIST indexes**: See [PostGIS Integration](#postgis-integration) section

**Performance impact**:
- Nearby queries: 500ms → < 1ms
- Segment detection: 2s → 200ms (for 100 segments)

### Code-Level Optimizations

1. **Use collections efficiently**: `pluck()`, `map()`, `filter()` instead of loops
2. **Avoid unnecessary queries**: Check relationships exist before querying
3. **Use chunk() for large datasets**: Process in batches instead of loading all at once
4. **Eager load relationships**: Always use `with()` to avoid N+1

**Example - Chunk processing**:
```php
Activity::chunk(1000, function ($activities) {
    foreach ($activities as $activity) {
        // Process activity
    }
});
```

---

## Monitoring & Observability

### Health Checks

**3 health check endpoints**:

#### Basic Liveness

```bash
GET /api/health

Response: {"status":"ok","timestamp":"2025-11-10T..."}
```

**Use case**: Load balancer health check, uptime monitoring

#### Readiness Check

```bash
GET /api/health/ready

Response: {
  "status": "ok",
  "database": "connected",
  "redis": "connected",
  "sentry": "configured"
}
```

**Use case**: Kubernetes readiness probe, deployment verification

#### Detailed Diagnostics

```bash
GET /api/health/detailed

Response: {
  "status": "ok",
  "database": {
    "status": "connected",
    "latency_ms": 1.2
  },
  "redis": {
    "status": "connected",
    "latency_ms": 0.3
  },
  "queue": {
    "driver": "redis",
    "jobs_pending": 5
  },
  "sentry": {
    "status": "configured",
    "dsn": "https://..."
  }
}
```

**Use case**: Troubleshooting, monitoring dashboards

### Laravel Telescope (Development Only)

**URL**: http://localhost:8000/telescope

**Features**:
- View all HTTP requests
- View all database queries (identify N+1)
- View Redis commands
- View queue jobs
- View exceptions
- View cache operations

**Disabled in production** for security and performance.

### Sentry (Error Tracking)

**Configuration** (`.env`):
```env
SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/project-id
```

**What gets logged**:
- All exceptions (500 errors)
- Failed queue jobs
- Custom errors via `Sentry::captureException()`

**Benefits**:
- Error aggregation (group similar errors)
- Stack traces with code context
- User identification (who experienced error)
- Release tracking (which version had error)

### Prometheus Metrics

**Endpoint**: `GET /api/metrics`

**Metrics exposed**:
- HTTP request duration (histogram)
- Database query duration (histogram)
- Queue job duration (histogram)
- Active users (gauge)
- Cache hit rate (counter)

**Prometheus config** (`prometheus.yml`):
```yaml
scrape_configs:
  - job_name: 'fittrack-api'
    scrape_interval: 15s
    static_configs:
      - targets: ['api.fittrack.com.br:80']
        labels:
          environment: 'production'
```

**Grafana Dashboard**: Import `monitoring/grafana-dashboard.json`

---

## Architectural Decisions

### Why No API Versioning in Routes?

We use **URL-based versioning** (`/api/v1/...`) instead of header-based versioning for:
- Simplicity (no custom headers)
- Discoverability (version visible in URL)
- Caching (CDN/proxy can cache per version)

### Why Data Classes Instead of Form Requests?

**Spatie Laravel Data** replaces both Form Requests and API Resources:
- Single source of truth (validation + transformation + serialization)
- Type-safe (backed by PHPStan Level 5)
- Less boilerplate code
- Better IDE autocomplete

**See**: `ADR-009` in `.claude/decisions/`

### Why Native PostGIS Instead of Packages?

**PHP packages** (like `brick/geo`) are slow for complex operations:
- Segment detection: ~30s with PHP, ~200ms with PostGIS
- Nearby queries: ~500ms with PHP, < 1ms with PostGIS

**PostGIS** has native spatial indexes (GIST) that PHP cannot replicate.

### Why Redis for Tracking Instead of Database?

**GPS tracking** generates thousands of writes per activity:
- PostgreSQL: ~100 inserts/sec (too slow)
- Redis: ~10,000 inserts/sec (fast enough)

**Redis TTL** (2 hours) provides automatic cleanup (no cron jobs needed).

---

## Questions?

**Want to contribute?** See [docs/contributing.md](contributing.md) for coding standards

**Need API reference?** See [docs/api.md](api.md) for all endpoints

**New to the project?** See [docs/onboarding.md](onboarding.md) for setup guide

**Found an issue?** Report in Slack: #backend-fittrack

---

**Last updated**: 2025-11-10 - FitTrack BR v1.0.0

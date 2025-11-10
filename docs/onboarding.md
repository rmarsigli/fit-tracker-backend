# FitTrack Backend - Onboarding Guide

> Welcome to FitTrack! This guide will get you from zero to running the app locally in ~30 minutes.

**Target audience**: New hired developers joining the backend team

---

## Prerequisites

Before you begin, install:

- **PHP 8.4+** - [Download](https://www.php.net/downloads)
- **Composer 2.x** - [Download](https://getcomposer.org/)
- **Docker Desktop** - [Download](https://www.docker.com/products/docker-desktop)
- **Git** - [Download](https://git-scm.com/)

**Optional but recommended**:
- **Postman** - For API testing
- **TablePlus** or **DBeaver** - For database GUI
- **Redis Insight** - For Redis GUI

---

## Setup Steps

### 1. Clone Repository

```bash
git clone <repository-url>
cd backend
```

### 2. Install Dependencies

```bash
composer install
```

This will take 2-3 minutes.

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

**Edit `.env`**: The defaults work for Docker Compose (next step). Don't change anything yet.

### 4. Start Services (Docker)

```bash
docker-compose up -d
```

This starts:
- **PostgreSQL 16** with **PostGIS 3.4** (port 5432)
- **Redis 7.2** (port 6379)

**Verify services are running**:
```bash
docker-compose ps

# Should show both postgres and redis as "Up"
```

### 5. Run Migrations

```bash
php artisan migrate
```

This creates all database tables with spatial indexes.

### 6. Seed Database (Optional but Recommended)

```bash
php artisan db:seed
```

This creates:
- 11 test users (`user1@test.com` / `password` ... `user11@test.com` / `password`)
- 100 activities with GPS routes
- 20 segments with start/end points
- 69 segment efforts (with KOM/QOM holders)
- Social relationships (follows, kudos, comments)
- 5 challenges (distance, duration, elevation)

**Pro tip**: Use these test accounts for API testing without creating new users every time.

### 7. Start Development Server

```bash
php artisan serve
```

App runs at: http://localhost:8000

### 8. Verify Installation

Open browser or curl:

```bash
# Health check
curl http://localhost:8000/api/health

# Expected response:
{"status":"ok","timestamp":"2025-11-10T..."}

# Detailed health check (includes DB + Redis + Sentry)
curl http://localhost:8000/api/health/detailed
```

If you see JSON responses, you're good! 🎉

---

## First API Request

Let's create an account and activity:

### 1. Register User

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Your Name",
    "username": "yourname",
    "email": "you@company.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Save the token** from response!

### 2. Create Activity

```bash
TOKEN="your-token-here"

curl -X POST http://localhost:8000/api/v1/activities \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "run",
    "title": "My First Run",
    "distance_meters": 5000,
    "duration_seconds": 1800,
    "started_at": "2025-11-10T06:00:00Z"
  }'
```

Success! You created your first activity. 🏃

---

## Development Tools

### 1. Postman Setup

**Import Collection**:
1. Open Postman
2. Import → File → Select `docs/postman-collection.json`
3. Create environment with `base_url` = `http://localhost:8000`
4. Register a user → Token auto-saved for all requests

Now you can test all endpoints without writing curl commands.

### 2. Database GUI (TablePlus)

**Connection Settings**:
- Host: `localhost`
- Port: `5432`
- User: `postgres`
- Password: `postgres`
- Database: `fittrack`

**Useful Queries**:

```sql
-- See all activities
SELECT id, user_id, type, title,
       distance_meters/1000 as distance_km,
       duration_seconds/60 as duration_min
FROM activities
ORDER BY created_at DESC
LIMIT 10;

-- See segments with efforts
SELECT s.name, s.distance_meters, COUNT(se.id) as efforts
FROM segments s
LEFT JOIN segment_efforts se ON s.id = se.segment_id
GROUP BY s.id
ORDER BY efforts DESC;

-- See leaderboard for segment 1
SELECT u.name,
       se.elapsed_time_seconds,
       se.average_speed_kmh,
       se.is_kom,
       se.is_pr
FROM segment_efforts se
JOIN users u ON se.user_id = u.id
WHERE se.segment_id = 1
ORDER BY se.elapsed_time_seconds ASC
LIMIT 10;

-- Check PostGIS extension
SELECT PostGIS_Version();
```

### 3. Laravel Tinker (Interactive Console)

```bash
php artisan tinker
```

**Useful commands**:

```php
// Get first user
$user = App\Models\User::first();

// Create activity using factory
$activity = App\Models\Activity\Activity::factory()->for($user)->create();

// Get all segments
$segments = App\Models\Segment\Segment::all();

// Test PostGIS (convert point to text)
$point = 'SRID=4326;POINT(-46.6333 -23.5505)';
DB::selectOne("SELECT ST_AsText(ST_GeomFromEWKT(?)) as point", [$point]);

// Get user with stats
$user = App\Models\User::with('activities')->first();
$user->activities->count();
```

---

## Common Development Tasks

### Run Tests

```bash
# All tests (236 tests, 866 assertions)
php artisan test

# Specific file
php artisan test tests/Feature/Api/v1/Activity/ActivityCrudTest.php

# Filter by name
php artisan test --filter=ActivityTracking

# With coverage (requires Xdebug)
php artisan test --coverage
```

### Code Quality Checks

```bash
# Format code (auto-fix)
vendor/bin/pint

# Check types (PHPStan Level 5)
composer phpstan

# Security audit
composer audit
```

**Run these before committing!** CI will reject PRs that fail these checks.

### Database Commands

```bash
# Reset database with fresh data
php artisan migrate:fresh --seed

# Create migration
php artisan make:migration create_something_table

# Rollback last migration
php artisan migrate:rollback

# Check migration status
php artisan migrate:status
```

### Queue Workers

Some features (segment detection) use background jobs:

```bash
# Start queue worker (foreground)
php artisan queue:work

# Or in another terminal:
php artisan queue:listen
```

**When do you need this?**
- When testing segment detection after finishing activities
- When testing any background processing
- Development: run `php artisan queue:work` in separate terminal

**Production**: Use Supervisor or systemd to keep queue workers running.

### Cache Management

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Or all at once:
php artisan optimize:clear
```

---

## Project Structure Overview

```
app/
├── Data/                    # DTOs (Request/Response) using Spatie Laravel Data
│   ├── Auth/               # Login, Register DTOs
│   ├── Activity/           # Activity DTOs (ActivityData, TrackingData, etc)
│   ├── Segment/            # Segment DTOs (SegmentData, LeaderboardData, etc)
│   ├── Social/             # Social DTOs (FollowData, LikeData, CommentData)
│   └── Challenge/          # Challenge DTOs
├── ValueObjects/            # Immutable domain values
│   ├── Common/             # Distance, Duration, Coordinates
│   └── Activity/           # Speed, Pace, HeartRate, Elevation
├── Enums/                  # Type-safe enumerations
│   ├── Activity/           # ActivityType, ActivityVisibility
│   └── Segment/            # SegmentType
├── Http/Controllers/       # API controllers
│   └── Api/v1/            # Version 1 API
│       ├── Auth/          # AuthController
│       ├── Activity/      # ActivityController, ActivityTrackingController
│       ├── Segment/       # SegmentController, SegmentLeaderboardController
│       ├── Social/        # FollowController, LikeController, CommentController, FeedController
│       ├── Challenge/     # ChallengeController
│       └── Statistics/    # StatisticsController
├── Models/                 # Eloquent models
│   ├── Activity/          # Activity, ActivityLike, ActivityComment
│   ├── Segment/           # Segment, SegmentEffort
│   ├── Social/            # Follow
│   ├── Challenge/         # Challenge, ChallengeParticipant
│   └── User.php           # User model
├── Services/               # Business logic (where the magic happens)
│   ├── Activity/          # ActivityTrackingService, StatisticsService
│   ├── PostGIS/           # PostGISService, GeoQueryService
│   ├── Segment/           # SegmentMatcherService
│   ├── Social/            # FeedService, FollowService
│   └── Challenge/         # ChallengeService
├── Jobs/                   # Queue jobs (background processing)
│   └── ProcessSegmentEfforts.php  # Detects segments after activity finishes
└── Console/Commands/       # Artisan commands (auto-registered)

tests/
├── Feature/               # Integration tests (API endpoint tests)
│   ├── Api/v1/           # API endpoint tests (220+ tests)
│   └── Services/         # Service tests
└── Unit/                 # Unit tests (less common in Laravel)

database/
├── migrations/           # Database schema (with PostGIS spatial indexes)
├── factories/            # Model factories for testing
└── seeders/             # Database seeders
```

**Key Concepts**:

- **Data Classes** (Spatie Laravel Data): Replace Form Requests + API Resources. Handle validation, transformation, and serialization.
- **ValueObjects**: Immutable domain values (Distance, Duration, etc). Type-safe and self-validating.
- **Services**: Business logic lives here, not in controllers. Controllers are thin.
- **PostGIS**: Native PostgreSQL extension for geospatial queries (no packages needed).

---

## Key Services Explained

### ActivityTrackingService

**Purpose**: Real-time GPS tracking with Redis

**How it works**:
1. `startTracking()` - Creates Redis key with 2-hour TTL
2. `trackLocation()` - Appends GPS points to Redis list
3. `pauseTracking()` / `resumeTracking()` - Update tracking status
4. `finishTracking()` - Moves GPS points from Redis → PostgreSQL, calculates metrics
5. Dispatches `ProcessSegmentEfforts` job to detect matching segments

**Location**: `app/Services/Activity/ActivityTrackingService.php`

### SegmentMatcherService

**Purpose**: Detect if activity route matches segments

**How it works**:
1. Gets all segments of same type (run/ride)
2. Uses PostGIS `ST_Intersection` to check route overlap
3. If overlap ≥ 90%, creates `SegmentEffort` record
4. Calculates elapsed time, speed, rank
5. Updates KOM/QOM if fastest time

**Location**: `app/Services/Segment/SegmentMatcherService.php`

### PostGISService

**Purpose**: Geospatial operations using native PostgreSQL + PostGIS

**Key methods**:
- `createPoint(lat, lng)` - Creates POINT geometry
- `createLineString(points)` - Creates LINESTRING from GPS points
- `calculateDistance(point1, point2)` - Distance in meters
- `findNearby(point, radius)` - Find segments near location

**Location**: `app/Services/PostGIS/PostGISService.php`

**Why not use a package?** Native PostGIS is faster and more powerful than any PHP package.

### StatisticsService

**Purpose**: Activity analytics and aggregations

**Key methods**:
- `getUserStatistics(user)` - Total, by type, last 7/30 days
- `calculateSplits(activity)` - Per-km splits with pace
- `calculatePaceZones(activity)` - 6 training zones based on avg pace

**Location**: `app/Services/Activity/StatisticsService.php`

### ChallengeService

**Purpose**: Manage challenges (distance, duration, elevation)

**Key methods**:
- `joinChallenge(user, challenge)` - Join a challenge
- `updateProgress(participant, activity)` - Update challenge progress
- `getLeaderboard(challenge)` - Get top participants

**Location**: `app/Services/Challenge/ChallengeService.php`

---

## Debugging Tips

### Enable Query Logging

Add to any controller or service:

```php
DB::enableQueryLog();

// ... your code ...

dd(DB::getQueryLog());
```

### Laravel Telescope (Local Only)

Start server and visit: http://localhost:8000/telescope

**What you can see**:
- All queries (find N+1 issues)
- All HTTP requests
- Redis commands
- Queue jobs
- Exceptions
- Cache operations

**WARNING**: Telescope is disabled in production (security + performance).

### Common Issues

**Issue**: `SQLSTATE[08006] Connection refused`
**Solution**: Docker services not running. Run `docker-compose up -d`

**Issue**: `SQLSTATE[42P01]: Undefined table`
**Solution**: Run migrations: `php artisan migrate`

**Issue**: `Class "PostGIS" not found`
**Solution**: PostGIS extension not installed. Check Docker logs: `docker-compose logs postgres`

**Issue**: Tests fail with "Database not found"
**Solution**: Tests use separate database config. Check `phpunit.xml` for `DB_DATABASE=testing`

**Issue**: Segment detection not working
**Solution**: Queue worker not running. Start: `php artisan queue:work`

**Issue**: Redis connection refused
**Solution**: Redis not running. Check: `docker-compose ps redis`

---

## Architecture Highlights

### PostGIS Integration

We use native PostgreSQL + PostGIS (no packages) for geospatial queries:

- **Activity routes**: Stored as `LINESTRING` geometry
- **Segment detection**: Uses `ST_Intersection` to check overlap
- **Distance calculations**: Uses `ST_Distance` with projection transform (3857)
- **Nearby search**: Uses `ST_DWithin` for efficient radius queries

**See**: `app/Services/PostGIS/` for implementation.

**Performance**: PostGIS queries typically < 1ms execution time with proper spatial indexes.

### Real-time GPS Tracking

Active GPS tracking uses Redis with 2-hour TTL:

1. Client starts tracking → Server creates Redis key `tracking:{userId}_{timestamp}`
2. Client sends GPS points every 5-10 seconds → Server appends to Redis list
3. Client finishes → Server saves to PostgreSQL + triggers segment detection
4. Redis key expires after 2 hours (automatic cleanup)

**Why Redis?** Fast writes (thousands of GPS points per activity), automatic expiration, low overhead.

**See**: `app/Services/Activity/ActivityTrackingService.php`

### Background Processing

Segment detection runs via Laravel queues:

1. Activity finished → `ProcessSegmentEfforts` job dispatched
2. Job finds matching segments via PostGIS (≥90% overlap)
3. Creates `SegmentEffort` records
4. Updates leaderboards (KOM/QOM)
5. Marks personal records (PR)

**Queue Driver**: Redis (configured in `.env`)

**Production**: Use Supervisor to keep queue workers running.

---

## Testing Philosophy

**Test Coverage**: 236 tests, 866 assertions, ~85% coverage

**Test Types**:
- **Feature tests** (220+): API endpoint tests, full request/response cycle
- **Unit tests** (16): Isolated logic tests (ValueObjects, Services)

**When to write tests**:
- ✅ **Always**: New API endpoints
- ✅ **Always**: Bug fixes (write failing test first)
- ✅ **Often**: Complex business logic in Services
- ⚠️ **Sometimes**: Simple CRUD operations (if covered by feature tests)

**Test Structure** (Pest):

```php
it('creates activity', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/activities', [
        'type' => 'run',
        'title' => 'Morning Run',
        'distance_meters' => 5000,
        'duration_seconds' => 1800,
        'started_at' => now()->toISOString(),
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['id', 'type', 'title']);

    expect(Activity::count())->toBe(1);
});
```

**Run tests before committing**: `php artisan test`

---

## Next Steps

Now that you're set up:

1. **Read the architecture docs**: [docs/architecture.md](architecture.md)
2. **Explore the API**: [docs/api.md](api.md)
3. **Check coding standards**: [docs/contributing.md](contributing.md)
4. **Look at existing tests**: `tests/Feature/Api/v1/`
5. **Join the team Slack**: #backend-fittrack

---

## Quick Reference

### Useful Commands

```bash
# Development
php artisan serve                    # Start dev server
php artisan queue:work              # Start queue worker
php artisan tinker                  # Interactive console

# Database
php artisan migrate:fresh --seed    # Reset DB with sample data
php artisan db:seed                 # Run seeders only

# Testing
php artisan test                    # Run all tests
php artisan test --filter=name      # Run specific test

# Code Quality
vendor/bin/pint                     # Format code
composer phpstan                    # Static analysis
composer audit                      # Security check

# Cache
php artisan optimize:clear          # Clear all caches
```

### Test Users (after seeding)

```
user1@test.com  / password
user2@test.com  / password
...
user11@test.com / password
```

### Important URLs

- **API Base**: http://localhost:8000/api/v1
- **Health Check**: http://localhost:8000/api/health
- **Telescope** (local only): http://localhost:8000/telescope
- **API Docs**: [docs/api.md](api.md)

---

## Questions?

**Stuck?** Ask in Slack: #backend-fittrack

**Bug in these docs?** Open a PR to fix it!

**Missing something?** Let the team know what would have helped.

---

Welcome to the team! 🎉

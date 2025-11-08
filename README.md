# FitTrack BR - Backend API

A fitness tracking platform API built with Laravel 12 and PostgreSQL + PostGIS, designed for the Brazilian market.

## Overview

A comprehensive fitness tracking application similar to Strava, allowing users to track their running, cycling, and other athletic activities with GPS data, compete on segments, participate in challenges, and connect with other athletes.

## Features (MVP)

### Completed ✅
- **User Authentication** - Registration, login, and profile management with Laravel Sanctum
- **Activity Tracking** - Real-time GPS tracking with distance, speed, elevation, and heart rate monitoring
- **Activity CRUD** - Complete create, read, update, delete operations
- **Statistics & Analytics** - Splits, pace zones, user aggregations, activity feed
- **Segments & Detection** - Create route segments with automatic detection and matching
- **Leaderboards** - KOM/QOM tracking, personal records, rankings (service layer ready)
- **Geolocation** - PostGIS-powered spatial queries for nearby activities and segments

### In Progress 🔄
- **Social Features** - Follow system, kudos, comments, feeds
- **Challenges** - Distance, duration, and elevation challenges

## Tech Stack

### Core
- **PHP** 8.4
- **Laravel** 12.37 (LTS)
- **PostgreSQL** 16 with PostGIS 3.4
- **Redis** 7.2 for caching and real-time tracking

### Key Packages
- `laravel/sanctum` v4 - API authentication
- `spatie/laravel-data` v4 - DTOs for validation & transformation
- `laravel/cashier` v16 - Payment processing (future)
- `pestphp/pest` v4 - Testing framework with browser support

### Testing
- **101 tests passing** ✅
- Pest 4 with Laravel plugin
- Feature, unit, and browser testing support
- ~70% code coverage

## Architecture Highlights

- **Native PostGIS Integration** - Custom SQL helpers for spatial queries (no third-party packages)
- **Data Classes + ValueObjects** - Modern architecture with Spatie Laravel Data
  - `Distance`, `Duration`, `Pace`, `Speed`, `HeartRate`, `Elevation`, `Coordinates`
  - Unified validation + transformation + API responses
- **Real-time Tracking** - Redis-based temporary storage (2h TTL) for active GPS tracking
- **Background Processing** - Queue jobs for segment detection and stats calculation
- **API Versioning** - Routes organized under `/api/v1/`
- **Smart File Organization** - Domain-driven folder structure
- **Type Safety** - Strict types, enums, and ValueObjects throughout

## Project Structure

```
app/
├── Data/                 # DTOs (Spatie Laravel Data) - replaces Form Requests + Resources
│   ├── Auth/
│   ├── Activity/
│   ├── Segment/
│   └── User/
├── ValueObjects/         # Immutable domain values with behavior
│   ├── Common/           # Distance, Duration
│   ├── Activity/         # Speed, Pace, HeartRate, Elevation
│   └── Geo/              # Coordinates (with Haversine distance)
├── Enums/                # Type-safe enumerations
│   ├── Activity/         # ActivityType, ActivityVisibility
│   └── Segment/          # SegmentType
├── Http/
│   ├── Controllers/Api/v1/  # API v1 controllers
│   ├── Requests/         # Form requests (being replaced by Data classes)
│   └── Resources/        # API resources (being replaced by Data classes)
├── Models/               # Eloquent models (organized by domain)
│   ├── Activity/
│   ├── Segment/
│   └── User.php
├── Services/             # Business logic services
│   ├── Activity/         # ActivityTrackingService, StatisticsService
│   ├── PostGIS/          # PostGISService, GeoQueryService
│   └── Segment/          # SegmentMatcherService
└── Jobs/                 # Queue jobs
    └── ProcessSegmentEfforts.php

database/
├── migrations/           # Database schema migrations
├── factories/            # Model factories for testing
└── seeders/              # Database seeders

tests/
├── Feature/              # Feature tests (API endpoints)
│   ├── Api/v1/
│   └── Services/
└── Unit/                 # Unit tests (future)

docs/
├── api.md                # Complete API documentation
└── postman-collection.json  # Postman collection for testing
```

## Database Schema (MVP)

**Core Tables:**
- `users` - User accounts with spatial location (Point)
- `activities` - GPS-tracked activities with routes (LineString)
- `segments` - Route segments for competition (LineString)
- `segment_efforts` - Individual attempts on segments with KOM/PR tracking
- `follows` - User follow relationships (future)
- `kudos` - Activity likes (future)
- `comments` - Activity comments (future)
- `challenges` - Distance/duration/elevation challenges (future)

**PostGIS Spatial Columns:**
- Activities: `route` (LineString), `start_point` (Point), `end_point` (Point)
- Segments: `route` (LineString), `start_point` (Point), `end_point` (Point)
- Users: `location` (Point)

**Indexes:**
- 7 GIST spatial indexes for optimal PostGIS query performance
- Composite indexes for common queries

## Installation

### Prerequisites
- PHP 8.4+
- PostgreSQL 16+ with PostGIS 3.4
- Redis 7.2+
- Composer 2.x
- Node.js & pnpm (for asset compilation)

### Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd runner/backend
   ```

2. **Install dependencies**
   ```bash
   composer install
   pnpm install
   ```

3. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database**

   Edit `.env` with your PostgreSQL credentials:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=fittrack
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Enable PostGIS extension**

   Connect to your PostgreSQL database and run:
   ```sql
   CREATE EXTENSION IF NOT EXISTS postgis;
   CREATE EXTENSION IF NOT EXISTS postgis_topology;
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed database (optional)**
   ```bash
   php artisan db:seed
   ```
   Creates: 11 users, 100 activities, 20 segments, 69 segment efforts

8. **Start development server**
   ```bash
   php artisan serve
   ```

9. **Access API**
   ```
   http://localhost:8000/api/v1
   ```

## Testing

Run the full test suite:
```bash
php artisan test
```

Run specific test file:
```bash
php artisan test tests/Feature/Api/v1/Activity/ActivityCrudTest.php
```

Run with filter:
```bash
php artisan test --filter=ActivityTracking
```

Current status: **101 tests passing** ✅

## Code Style

This project follows Laravel best practices and custom conventions:

- **Strong typing** - All methods have return type declarations
- **Strict types** - `declare(strict_types=1)` in all PHP files
- **PHP 8+ features** - Constructor property promotion, readonly properties, enums
- **Data classes** - Spatie Laravel Data for DTOs (validation + transformation + responses)
- **ValueObjects** - Immutable domain values (Distance, Duration, Pace, etc.)
- **No inline comments** - PHPDoc only when necessary
- **Pest syntax** - All tests written in Pest 4

Format code with Laravel Pint:
```bash
vendor/bin/pint
```

## API Documentation

Complete API documentation available in **`docs/api.md`**

### Endpoints (31 total)

**Authentication** (`/api/v1/auth/*`):
- POST `/register` - Create account
- POST `/login` - Authenticate
- GET `/me` - Current user
- POST `/logout` - Revoke token

**Activities** (`/api/v1/activities/*`):
- GET `/activities` - List user's activities
- POST `/activities` - Create activity
- GET `/activities/{id}` - Show activity
- PATCH `/activities/{id}` - Update activity
- DELETE `/activities/{id}` - Delete activity

**Activity Tracking** (`/api/v1/tracking/*`):
- POST `/tracking/start` - Start tracking
- POST `/tracking/{id}/track` - Add GPS point
- POST `/tracking/{id}/pause` - Pause tracking
- POST `/tracking/{id}/resume` - Resume tracking
- GET `/tracking/{id}/status` - Get status
- POST `/tracking/{id}/finish` - Finish & save

**Statistics** (`/api/v1/statistics/*`):
- GET `/statistics/me` - User stats
- GET `/statistics/feed` - Activity feed
- GET `/statistics/activities/{id}/splits` - Per-km splits
- GET `/statistics/activities/{id}/pace-zones` - Pace zones

**Segments** (`/api/v1/segments/*`):
- GET `/segments` - List segments
- POST `/segments` - Create segment
- GET `/segments/{id}` - Show segment
- PATCH `/segments/{id}` - Update segment
- DELETE `/segments/{id}` - Delete segment
- GET `/segments/nearby` - Find nearby segments

### Postman Collection

Import **`docs/postman-collection.json`** into Postman for quick API testing.

Features:
- All 31 endpoints pre-configured
- Auto-save authentication token
- Auto-save tracking ID
- Example request bodies
- Collection variables for base URL

## Development Status

**MVP Progress**: 60% Complete (3/5 SCRUMs)

- ✅ **SCRUM 1** - Foundation & Database Setup (100%)
  - PostGIS setup & migrations
  - Models, factories, seeders
  - Authentication with Sanctum

- ✅ **SCRUM 2** - Activities Core Features (100%)
  - Activity CRUD
  - Real-time GPS tracking
  - Statistics & analytics

- ✅ **SCRUM 3** - Geolocation & Segments (100%)
  - PostGIS helpers & geo services
  - Segment CRUD & nearby search
  - Automatic segment detection with KOM/PR tracking

- 🔄 **SCRUM 4** - Social Features (Next)
  - Follow system & feeds
  - Kudos & comments
  - Advanced feeds

- ⏳ **SCRUM 5** - Challenges & Polish
  - Challenges system
  - Performance optimization
  - API documentation & deployment

See `.claude/current-sprint.md` for detailed sprint planning.

## Architecture Decisions

Key architectural decisions documented in `.claude/decisions/`:

- **ADR-001**: PostGIS Native (no packages)
- **ADR-002**: Real-time Tracking with Redis
- **ADR-003**: API Versioning Strategy
- **ADR-004**: Validation via Form Requests
- **ADR-005**: Smart Files Organization
- **ADR-006**: Testing with Pest 4
- **ADR-007**: Segment Detection Strategy
- **ADR-008**: Enums Directory Structure
- **ADR-009**: Data Classes & ValueObjects Architecture ⭐

## Future Features (Post-MVP)

### Phase 2 - B2B Features
- Training plans for coaches
- Athlete-coach relationship management
- Workout scheduling and tracking

### Phase 3 - Social Expansion
- Clubs and group activities
- Club challenges and leaderboards
- Group events

### Phase 4 - Integrations
- Strava/Garmin OAuth and import
- GPX/TCX/FIT file import/export
- Wearable device integrations

### Phase 5 - Premium
- Payment integration (Stripe)
- Subscription management
- Premium features

### Phase 6 - Advanced
- Push notifications
- Heat maps generation
- Route builder
- Advanced analytics
- Weather integration

## Contributing

This is a private project. See sprint documentation in `.claude/` for development workflow.

## License

Proprietary - All rights reserved

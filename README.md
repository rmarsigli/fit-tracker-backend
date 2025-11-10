# FitTrack BR - Backend API

[![Tests](https://github.com/rmarsigli/fit-tracker-backend/actions/workflows/tests.yml/badge.svg)](https://github.com/rmarsigli/fit-tracker-backend/actions/workflows/tests.yml)
[![PHPStan Level 5](https://img.shields.io/badge/PHPStan-level%205-brightgreen.svg?style=flat-square)](https://phpstan.org/)
[![Code Style](https://img.shields.io/badge/code%20style-pint-orange.svg?style=flat-square)](https://laravel.com/docs/pint)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4.svg?style=flat-square&logo=php)](https://www.php.net/)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20.svg?style=flat-square&logo=laravel)](https://laravel.com) 

**A fitness tracking API built for Brazilian athletes.** Track runs, rides, and workouts with GPS precision, compete on segments, and connect with your fitness community.

---

## What is Runner App?

FitTrack BR is a comprehensive fitness tracking platform designed for the Brazilian market. Think Strava, but built from the ground up with modern Laravel architecture and powerful spatial features.

**What you can do**:
- 🏃 Track activities with real-time GPS, heart rate, and elevation
- 🗺️ Create and compete on route segments (like Strava's KOMs)
- 📊 Analyze performance with splits, pace zones, and detailed statistics
- 👥 Follow friends, give kudos, and comment on activities
- 🏆 Join distance and elevation challenges
- 🌍 Discover nearby activities and popular routes

---

## Quick Start

Get up and running in under 5 minutes:

```bash
# 1. Clone and install
git clone <repository-url>
cd runner/backend
composer install

# 2. Setup environment
cp .env.example .env
php artisan key:generate

# 3. Configure database (PostgreSQL with PostGIS required)
# Edit .env with your database credentials

# 4. Enable PostGIS extension
psql your_database -c "CREATE EXTENSION IF NOT EXISTS postgis;"

# 5. Run migrations
php artisan migrate

# 6. Start server
php artisan serve

# 🎉 API is now running at http://localhost:8000/api/v1
```

**Next steps**: See [docs/onboarding.md](docs/onboarding.md) for detailed setup and [docs/api.md](docs/api.md) for API documentation.

---

## Tech Stack

**Core**:
- PHP 8.4 + Laravel 12 (latest LTS)
- PostgreSQL 16 + PostGIS 3.4 (spatial features)
- Redis 7.2 (caching and real-time tracking)

**Key Features**:
- Modern architecture with Data classes and ValueObjects
- Native PostGIS integration (no third-party packages)
- Real-time GPS tracking with Redis
- Background segment detection with queues
- Comprehensive testing (236 tests passing)
- PHPStan Level 5 (strict type safety)
- Automated CI/CD with GitHub Actions

---

## What's Included

### Core Features

**Authentication & Users**
- Registration, login, and profile management
- Token-based authentication (Laravel Sanctum)

**Activity Tracking**
- Real-time GPS tracking with pause/resume
- Distance, speed, elevation, and heart rate monitoring
- Complete CRUD operations for activities

**Statistics & Analytics**
- Per-kilometer splits
- Pace zone analysis
- User aggregations and activity feed

**Segments & Leaderboards**
- Create custom route segments
- Automatic segment detection using PostGIS
- KOM/QOM (King/Queen of Mountain) tracking
- Personal records and rankings

**Social Features**
- Follow system
- Kudos (activity likes)
- Comments and activity feed

**Challenges**
- Distance, duration, and elevation challenges
- Progress tracking and leaderboards

---

## API Documentation

**36 REST endpoints** organized under `/api/v1/`:

- **Authentication**: `/auth/*` (register, login, logout)
- **Activities**: `/activities/*` (CRUD, list, search)
- **Tracking**: `/tracking/*` (start, track, pause, resume, finish)
- **Statistics**: `/statistics/*` (user stats, splits, pace zones)
- **Segments**: `/segments/*` (CRUD, nearby search, leaderboards)
- **Social**: Follow, kudos, comments, feed
- **Challenges**: Create, join, progress tracking

**Full API documentation**: [docs/api.md](docs/api.md)

**Postman Collection**: Import `docs/postman-collection.json` for quick testing with pre-configured requests.

---

## Database Schema

**Main tables**:
- `users` - User accounts with spatial location
- `activities` - GPS-tracked activities with routes (PostGIS LineString)
- `segments` - Route segments for competition
- `segment_efforts` - Individual attempts on segments
- `follows` - User follow relationships
- `kudos` - Activity likes
- `comments` - Activity comments
- `challenges` - Distance/duration challenges

**PostGIS spatial columns**: Activities and segments store GPS routes as LineStrings with seven GIST indexes for optimal spatial query performance.

---

## Development

### Running Tests

```bash
# Run all tests (236 tests, 866 assertions)
php artisan test

# Run specific test file
php artisan test tests/Feature/Api/v1/Activity/ActivityCrudTest.php

# Run with filter
php artisan test --filter=ActivityTracking
```

### Code Quality

This project maintains production-grade code quality:

```bash
# Format code (Laravel Pint)
vendor/bin/pint

# Static analysis (PHPStan Level 5)
composer phpstan

# Security audit
composer audit
```

**Standards**:
- Strict types in all PHP files
- PHPStan Level 5 (zero errors, zero suppressions)
- PSR-12 code style
- All tests passing before merge
- Automated CI/CD checks

---

## Project Structure

```
app/
├── Data/              # DTOs with validation (Spatie Laravel Data)
├── ValueObjects/      # Immutable domain values (Distance, Duration, Pace)
├── Enums/             # Type-safe enumerations
├── Http/Controllers/  # API v1 controllers
├── Models/            # Eloquent models (organized by domain)
├── Services/          # Business logic (ActivityTracking, SegmentMatcher)
└── Jobs/              # Queue jobs (segment detection)

database/
├── migrations/        # Database schema
├── factories/         # Model factories for testing
└── seeders/           # Database seeders

tests/
├── Feature/           # API endpoint tests
└── Unit/              # Unit tests

docs/
├── api.md             # Complete API documentation
├── onboarding.md      # New developer guide
├── architecture.md    # System architecture overview
└── contributing.md    # Development guidelines
```

---

## Architecture Highlights

**Modern Laravel Architecture**:
- Data classes for DTOs (replaces Form Requests + API Resources)
- ValueObjects for domain concepts (Distance, Duration, Pace, Speed)
- Strong typing throughout (PHP 8.4 features plus PHPStan Level 5)
- Smart file organization (domain-driven structure)

**PostGIS Integration**:
- Custom SQL helpers for spatial queries
- No third-party packages needed
- Seven GIST indexes for performance
- Efficient segment detection using spatial intersections

**Real-time Tracking**:
- Redis-based temporary storage (2h TTL)
- Background processing with queues
- Automatic segment detection on activity completion

**For more details**: See [docs/architecture.md](docs/architecture.md)

---

## Requirements

- PHP 8.4 or higher
- PostgreSQL 16+ with PostGIS 3.4 extension
- Redis 7.2+ for caching and queues
- Composer 2.x for PHP dependencies

---

## Production Ready

This application is **production-ready** with:

- ✅ 236 tests passing (866 assertions)
- ✅ PHPStan Level 5 - zero errors
- ✅ Zero security vulnerabilities
- ✅ Health checks and metrics endpoints
- ✅ Error monitoring (Sentry integration)
- ✅ Automated CI/CD pipeline
- ✅ Comprehensive documentation

**Deployment guide**: See [docs/deployment.md](docs/deployment.md) (coming soon)

---

## Contributing

See [docs/contributing.md](docs/contributing.md) for:
- Development workflow
- Testing requirements
- Code standards
- Pull request process

---

## License

Proprietary - All rights reserved

---

## Support

For questions or issues:
- Read the docs in `/docs`
- Check existing GitHub issues
- Contact the development team

**New to the project?** Start with [docs/onboarding.md](docs/onboarding.md) for a complete setup guide.

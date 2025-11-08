# ~Another~ Fit Tracker App - Backend API

A fitness tracking platform API built with Laravel 12 and PostgreSQL + PostGIS, initially designed for the Brazilian market.

## Overview

THis is a comprehensive fitness tracking application similar to Strava, allowing users to track their running, cycling, and other athletic activities with GPS data, compete on segments, participate in challenges, and connect with other athletes.

## Features (MVP)

- **User Authentication** - Registration, login, and profile management with Laravel Sanctum
- **Activity Tracking** - Real-time GPS tracking with distance, speed, elevation, and heart rate monitoring
- **Segments & Leaderboards** - Create route segments and compete for KOM/QOM (King/Queen of Mountain)
- **Social Features** - Follow athletes, give kudos, comment on activities, and view personalized feeds
- **Challenges** - Join distance, duration, or elevation challenges and track progress
- **Geolocation** - PostGIS-powered spatial queries for nearby activities, segments, and athletes

## Tech Stack

### Core
- **PHP** 8.4
- **Laravel** 12.37 (LTS)
- **PostgreSQL** 16 with PostGIS 3.4
- **Redis** 7.2 for caching and queues

### Key Packages
- `laravel/sanctum` - API authentication
- `laravel/cashier` - Payment processing (future)
- `spatie/laravel-data` - DTOs and data objects
- `spatie/laravel-permission` - Roles and permissions (future)
- `pestphp/pest` - Testing framework

### Testing
- Pest 4 with Laravel plugin
- Browser testing support
- Feature and unit tests

## Architecture Highlights

- **Native PostGIS Integration** - Custom SQL helpers for spatial queries (no third-party packages)
- **Real-time Tracking** - Redis-based temporary storage for active GPS tracking
- **Background Processing** - Queue jobs for segment detection and stats calculation
- **API Versioning** - Routes organized under `/api/v1/`
- **Smart File Organization** - Domain-driven folder structure
- **Repository Pattern** - Complex queries abstracted into repositories

## Project Structure

```
app/
├── Actions/              # Single-purpose action classes
├── Data/                 # DTOs (Spatie Laravel Data)
├── Enums/                # Type-safe enumerations
├── Http/
│   ├── Controllers/Api/V1/  # API v1 controllers
│   ├── Requests/         # Form request validation
│   └── Resources/        # API response resources
├── Models/               # Eloquent models (organized by domain)
├── Services/             # Business logic services
└── Repositories/         # Database query abstractions

database/
├── migrations/           # Database schema migrations
├── factories/            # Model factories for testing
└── seeders/              # Database seeders

tests/
├── Feature/              # Feature tests (API endpoints)
├── Unit/                 # Unit tests (services, helpers)
└── Browser/              # Browser tests (Pest 4)
```

## Database Schema (MVP)

**Core Tables:**
- `users` - User accounts with spatial location
- `activities` - GPS-tracked activities with routes (LineString)
- `segments` - Route segments for competition
- `segment_efforts` - Individual attempts on segments
- `follows` - User follow relationships
- `kudos` - Activity likes
- `comments` - Activity comments
- `challenges` - Distance/duration/elevation challenges
- `challenge_participants` - Challenge participation and progress

**PostGIS Columns:**
- Activities: `route` (LineString), `start_point` (Point), `end_point` (Point)
- Segments: `route` (LineString), `start_point` (Point), `end_point` (Point)
- Users: `location` (Point)

## Installation

### Prerequisites
- PHP 8.4+
- PostgreSQL 16+
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

8. **Start development server**
   ```bash
   php artisan serve
   ```

## Testing

Run the full test suite:
```bash
php artisan test
```

Run specific test file:
```bash
php artisan test tests/Feature/ActivityTest.php
```

Run with coverage:
```bash
php artisan test --coverage
```

Filter by test name:
```bash
php artisan test --filter=testUserCanCreateActivity
```

## Code Style

This project follows Laravel best practices and custom conventions:

- **Strong typing** - All methods have return type declarations
- **PHP 8+ features** - Constructor property promotion, enums, etc.
- **No inline comments** - PHPDoc only when necessary
- **Pest syntax** - All tests written in Pest 4

Format code with Laravel Pint:
```bash
vendor/bin/pint
```

## API Documentation

API endpoints are organized under `/api/v1/`:

- **Authentication** - `/api/v1/auth/*`
- **Activities** - `/api/v1/activities/*`
- **Segments** - `/api/v1/segments/*`
- **Users** - `/api/v1/users/*`
- **Feed** - `/api/v1/feed/*`
- **Challenges** - `/api/v1/challenges/*`

Detailed API documentation available in `.claude/current-sprint.md`

## Development Status

🚧 **Currently in Development** - MVP Phase

- ✅ Project setup and planning complete
- 🔄 Sprint 1.1 - Database schema (In Progress)
- ⏳ 14 sprints remaining

See `.claude/current-sprint.md` for detailed sprint planning.

## Future Features (Post-MVP)

- Training plans for coaches (B2B)
- Clubs and group activities
- Strava/Garmin integration
- GPX/TCX/FIT file import/export
- Payment integration (premium features)
- Push notifications
- Advanced analytics and insights
- Route builder
- Heat maps

## License

Proprietary - All rights reserved

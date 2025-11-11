# ADR-004: Do Not Implement Repository Pattern

**Status**: ✅ Accepted (Do Not Implement)
**Date**: 2025-11-10
**Deciders**: Claude Code, Rafhael
**Context**: Architectural improvements sprint (2025-11-10)

## Context and Problem

It was suggested to implement Repository Pattern to centralize PostGIS queries:

```php
// Proposal: app/Repositories/ActivityRepository.php
class ActivityRepository
{
    public function findNearby(float $lat, float $lng, float $radiusKm, int $limit = 20): Collection
    {
        return Activity::query()
            ->selectRaw('*, ST_Distance(...) as distance_meters', [$lng, $lat])
            ->whereRaw('ST_DWithin(...)', [$lng, $lat, $radiusKm * 1000])
            ->orderBy('distance_meters')
            ->limit($limit)
            ->get();
    }
}

// Usage in Service
class GeoQueryService
{
    public function __construct(protected ActivityRepository $activityRepo) {}

    public function findActivitiesNear($lat, $lng)
    {
        return $this->activityRepo->findNearby($lat, $lng, 5.0);
    }
}
```

**Arguments in favor**:
- ✅ Queries centralized in one place
- ✅ Easy to mock in tests
- ✅ Abstraction between Service and Model

**Arguments against**:
- ❌ Adds extra layer: Controller → Service → Repository → Model
- ❌ PostGIS queries remain complex in Repository
- ❌ Over-engineering for medium-sized system (< 100 models)
- ❌ Laravel Eloquent already abstracts well

## Decision

**DO NOT implement Repository Pattern.**

Keep current architecture: **Controller → Service → Model**

**Reasons**:

### 1. Laravel Eloquent is already a Repository Pattern
```php
// Eloquent is ALREADY a database abstraction
Activity::query()
    ->where('user_id', 123)
    ->with('user')
    ->get();

// Don't need:
$this->activityRepo->findByUserId(123);
```

### 2. PostGIS queries don't become simpler
```php
// In Repository, we'd still have complex SQL:
class ActivityRepository
{
    public function findNearby(...)
    {
        return Activity::query()
            ->selectRaw('ST_Distance(ST_Transform(route::geometry, 3857), ...) as distance')
            ->whereRaw('ST_DWithin(...)')  // ❌ Still complex!
            ->get();
    }
}
```

### 3. Makes mocking harder in tests
```php
// Before (simple):
$this->mock(GeoQueryService::class)
    ->shouldReceive('findNearby')
    ->andReturn([...]);

// After (need to mock Repository too):
$this->mock(ActivityRepository::class)
    ->shouldReceive('findNearby')
    ->andReturn([...]);

$this->mock(GeoQueryService::class)  // And still need to mock Service!
    ->shouldReceive('findActivitiesNear')
    ->andReturn([...]);
```

### 4. Over-engineering for our size
```
Current system:
- ~20 Models
- ~10 Services
- ~30 Controllers

System that needs Repository:
- 100+ Models
- 50+ Services
- Multiple ORMs (Eloquent + MongoDB + Redis)
```

### 5. Services already do the Repository's job
```php
// GeoQueryService ALREADY centralizes PostGIS queries
class GeoQueryService
{
    public function findActivitiesNear(...) { }
    public function findSegmentsNear(...) { }
    public function findIntersectingSegments(...) { }
}

// Adding Repository would be duplication:
class ActivityRepository
{
    public function findNear(...) { }  // Duplicates GeoQueryService
}
```

## Consequences

### Positive ✅
- **Simpler architecture**: 3 layers instead of 4
- **Less boilerplate**: Don't need to create N repositories
- **Direct Eloquent**: Leverage all Laravel features
- **Easy maintenance**: Less code = fewer bugs
- **Team familiarity**: Team already knows Eloquent + Services

### Negative ❌
- **Scattered queries**: Some queries might be in Services, others in Controllers
  - *Mitigation*: Create specific Services (GeoQueryService, SegmentMatcherService)
- **Difficult to change ORM**: If ever need to migrate Eloquent → MongoDB
  - *Mitigation*: Unlikely, PostgreSQL + PostGIS is perfect for this domain

### Neutral ⚖️
- **Not an anti-pattern**: Repository is optional, not mandatory
- **Can change later**: If system grows 10x, we can refactor

## Alternatives Considered

### 1. Repository Pattern (complete)
```
Controller → Service → Repository → Model → Database
```
**Rejected**: Over-engineering, adds unnecessary layer

### 2. Service Pattern (current) ✅ CHOSEN
```
Controller → Service → Model → Database
```
**Accepted**: Simple, sufficient, familiar

### 3. Fat Controllers (anti-pattern)
```
Controller → Model → Database
```
**Rejected**: Controllers would become complex, scattered business logic

## When to Reevaluate

Implement Repository Pattern IF:
- [ ] System grows to 100+ models
- [ ] Need multiple ORMs (Eloquent + MongoDB + Redis)
- [ ] Need to easily change databases
- [ ] Queries become too repetitive across Services
- [ ] Team grows to 10+ devs (consistency more important)

**Probability of reevaluation**: Low (~5%)

## Implementation (Not Implemented)

- **Estimate**: 8 hours to implement
- **ROI**: ⭐ (Low)
- **Status**: ❌ Not implemented
- **Decision**: Keep current architecture (Service Pattern)

## References

- Laravel Repositories (discussion): https://github.com/laravel/framework/discussions/31765
- Why I don't use repositories: https://laravel-news.com/repository-pattern
- Repository vs Service Layer: https://stackoverflow.com/questions/5049363

## Links

- Sprint: Sprint 2025-11-10 optimization
- Current architecture: `app/Services/PostGIS/GeoQueryService.php`
- Tests: `tests/Feature/Services/GeoQueryServiceTest.php`

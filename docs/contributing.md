# Contributing to FitTrack Backend

> Internal contribution guide for FitTrack development team.

**Target audience**: Backend developers on the FitTrack team

---

## Table of Contents

1. [Development Workflow](#development-workflow)
2. [Code Standards](#code-standards)
3. [Testing Requirements](#testing-requirements)
4. [Common Patterns](#common-patterns)
5. [Before Committing](#before-committing)
6. [Code Review Guidelines](#code-review-guidelines)
7. [Performance Guidelines](#performance-guidelines)

---

## Development Workflow

### 1. Pick a Task

Tasks are tracked in: [Your Project Management Tool]

**Before starting**:
- Assign task to yourself
- Move to "In Progress"
- Read acceptance criteria
- Ask questions if unclear

### 2. Create Branch

```bash
git checkout main
git pull origin main
git checkout -b feature/your-feature-name

# Or for bugs:
git checkout -b fix/bug-description

# Or for refactors:
git checkout -b refactor/simplify-something
```

**Branch naming conventions**:
- Features: `feature/segment-leaderboards`
- Bugs: `fix/tracking-redis-timeout`
- Refactors: `refactor/simplify-postgis-service`
- Docs: `docs/update-api-documentation`

### 3. Make Changes

**Write code following our standards** (see below).

**Run checks frequently**:
```bash
vendor/bin/pint              # Format code
composer phpstan             # Check types
php artisan test --filter=YourFeature  # Run related tests
```

### 4. Commit

```bash
git add .
git commit -m "feat: add segment leaderboards

- Create SegmentLeaderboardController
- Add 5 new endpoints (leaderboard, KOM/QOM, records)
- Write 12 tests (all passing)
- Update API documentation"
```

**Commit message format**:
- Start with type: `feat:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:`
- Short summary (< 72 chars)
- Blank line
- Detailed explanation (bullet points preferred)

**Commit types**:
- `feat:` - New feature
- `fix:` - Bug fix
- `refactor:` - Code refactoring (no behavior change)
- `docs:` - Documentation only
- `test:` - Adding or updating tests
- `chore:` - Maintenance (dependencies, configs, etc)

### 5. Push & Create PR

```bash
git push origin feature/your-feature-name
```

**Create Pull Request on GitHub**:
- Title: Same as commit summary
- Description: Use PR template (see below)
- Link to task/ticket
- Add screenshots if UI-related (not applicable for API)
- Request reviewers

**PR Template**:
```markdown
## What
Brief description of changes (1-2 sentences)

## Why
Why these changes were needed (business value, bug impact, etc)

## How to Test
1. Run migrations (if database changes): `php artisan migrate`
2. Seed test data (if needed): `php artisan db:seed`
3. Test endpoint:
   ```bash
   curl -X POST http://localhost:8000/api/v1/... \
     -H "Authorization: Bearer {token}" \
     -d '...'
   ```
4. Expected result: ...

## Checklist
- [ ] Tests passing (`php artisan test`)
- [ ] PHPStan passing (`composer phpstan`)
- [ ] Code formatted (`vendor/bin/pint`)
- [ ] Documentation updated (if needed)
- [ ] No N+1 queries (checked with Telescope)
- [ ] No breaking changes (or documented in PR description)
```

### 6. Code Review

**Reviewers will check**:
- Code quality and readability
- Test coverage (happy path + edge cases)
- Documentation (if public API changes)
- No breaking changes (or properly communicated)
- Performance (no obvious bottlenecks)

**Be ready to**:
- Answer questions promptly
- Make requested changes
- Explain technical decisions

### 7. Merge

Once approved:
- **Squash and merge** (if multiple commits)
- Delete branch after merge
- Move task to "Done"

---

## Code Standards

### General Rules

1. **PHPStan Level 5** - Zero errors, zero suppressions (`@phpstan-ignore` not allowed)
2. **Laravel Pint** - PSR-12 formatting (auto-fix before commit)
3. **Type hints everywhere** - Parameters, return types, properties
4. **`declare(strict_types=1)`** - First line of every PHP file (all in one line)
5. **Tests for everything** - No PR without tests

### File Organization

**Smart Files Organization** - Group related files:

```
app/Models/Activity/
├── Activity.php
├── ActivityLike.php
└── ActivityComment.php

app/Services/Activity/
├── ActivityTrackingService.php
└── StatisticsService.php

app/Data/Activity/
├── ActivityData.php
├── TrackingData.php
└── StatisticsData.php
```

**Namespace must match folder structure**:

```php
<?php declare(strict_types=1);

namespace App\Services\Activity;

class ActivityTrackingService
{
    // ...
}
```

### Naming Conventions

**Classes**: PascalCase
```php
class SegmentMatcherService {}
class ActivityData {}
class ActivityType {}
```

**Methods**: camelCase (descriptive, action verbs)
```php
public function calculateDistance(): float {}
public function getUserActivities(): Collection {}
public function isEligibleForChallenge(): bool {}
```

**Variables**: camelCase
```php
$activityData = ...;
$userId = ...;
$segmentEffort = ...;
```

**Constants**: SCREAMING_SNAKE_CASE
```php
const MAX_TRACKING_DURATION = 86400;  // 24 hours
const MIN_SEGMENT_DISTANCE = 100;     // 100 meters
```

**Database columns**: snake_case
```php
distance_meters
moving_time_seconds
is_kom
```

### Type Declarations

**Always explicit return types**:

```php
// ✅ Good
public function store(ActivityData $data): Activity
{
    return Activity::create($data->toArray());
}

// ❌ Bad - missing return type
public function store(ActivityData $data)
{
    return Activity::create($data->toArray());
}
```

**Use specific types** (not `mixed`):

```php
// ✅ Good
public function getActivities(): Collection
public function findActivity(int $id): ?Activity
public function isPublic(): bool

// ❌ Bad - too generic
public function getActivities(): mixed
public function findActivity(int $id): object|null
public function isPublic(): int  // Use bool!
```

### PHPDoc Blocks

**Only when adding information not in signature**:

```php
// ✅ Good - adds info not in signature
/**
 * @return Collection<int, Activity>
 */
public function getActivities(): Collection

/**
 * @property-read float $distance_km
 * @property-read string $duration_formatted
 */
class Activity extends Model

// ❌ Bad - repeats what's obvious
/**
 * Gets the user
 * @return User
 */
public function user(): User
```

### Data Classes (Spatie Laravel Data)

**Use Data classes for DTOs**:

```php
<?php declare(strict_types=1);

namespace App\Data\Activity;

use Spatie\LaravelData\Data;

class ActivityData extends Data
{
    public function __construct(
        public string $type,
        public string $title,
        public ?string $description,
        public ?float $distance_meters,
        public ?int $duration_seconds,
        public string $started_at,
    ) {}
}
```

**Benefits**:
- Validation (built-in)
- Transformation (fromRequest, fromModel, etc)
- Type safety (PHPStan Level 5)
- Automatic API responses (no need for API Resources)

**Usage in controllers**:

```php
public function store(ActivityData $data): JsonResponse
{
    $activity = Activity::create($data->toArray());

    return response()->json(ActivityData::from($activity), 201);
}
```

### Services (Business Logic)

**Business logic goes in Services**, not controllers:

```php
<?php declare(strict_types=1);

namespace App\Services\Activity;

use App\Models\Activity\Activity;
use App\Models\User;

class ActivityTrackingService
{
    public function __construct(
        private readonly PostGISService $postGIS
    ) {}

    public function startTracking(User $user, string $type): string
    {
        // Business logic here
        $trackingId = "tracking_{$user->id}_" . time();

        Redis::setex($trackingId, 7200, json_encode([
            'user_id' => $user->id,
            'type' => $type,
            'started_at' => now()->toISOString(),
            'points' => [],
        ]));

        return $trackingId;
    }
}
```

**Controllers are thin** (orchestration only):

```php
public function store(ActivityData $data): JsonResponse
{
    $activity = $this->activityService->create(auth()->user(), $data);

    return response()->json(ActivityData::from($activity), 201);
}
```

### Eloquent Best Practices

**Always use relationship methods**:

```php
// ✅ Good
$user->activities()->where('type', 'run')->get();
$activity->user;
$segment->efforts()->with('user')->get();

// ❌ Bad - manual joins
Activity::join('users', 'activities.user_id', '=', 'users.id')
    ->where('users.id', $userId)
    ->get();
```

**Eager load to prevent N+1**:

```php
// ✅ Good
$activities = Activity::with('user', 'likes')->get();

// ❌ Bad - N+1 query
$activities = Activity::all();
foreach ($activities as $activity) {
    echo $activity->user->name;  // N+1!
}
```

**Use query scopes for common filters**:

```php
// In Activity model
public function scopePublic($query)
{
    return $query->where('visibility', 'public');
}

public function scopeCompleted($query)
{
    return $query->whereNotNull('completed_at');
}

// Usage
Activity::public()->completed()->latest()->get();
```

---

## Testing Requirements

### Test Coverage

**Required**:
- ✅ All API endpoints (feature tests)
- ✅ All Services with complex logic (feature or unit tests)
- ✅ All bug fixes (write failing test first, then fix)

**Optional**:
- Simple CRUD operations (if covered by feature tests)
- Getters/setters (if no logic)

### Pest Test Structure

**All tests use Pest** (not PHPUnit):

```php
<?php declare(strict_types=1);

use App\Models\User;
use App\Models\Activity\Activity;

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
        ->assertJsonStructure(['id', 'type', 'title', 'distance_meters']);

    expect(Activity::count())->toBe(1);
});
```

### Test Coverage Requirements

**Test happy path + edge cases**:

```php
// ✅ Happy path
it('creates activity with valid data', function () { ... });

// ✅ Validation failures
it('fails when type is missing', function () { ... });
it('fails when distance is negative', function () { ... });
it('fails when completed_at is before started_at', function () { ... });

// ✅ Authorization
it('fails when user is not authenticated', function () { ... });
it('fails when user tries to update another user\'s activity', function () { ... });

// ✅ Edge cases
it('handles activity with zero distance', function () { ... });
it('handles activity with no GPS data', function () { ... });
```

### Pest Assertions

**Use specific assertion methods**:

```php
// ✅ Good - specific methods
$response->assertCreated();       // 201
$response->assertSuccessful();    // 2xx
$response->assertForbidden();     // 403
$response->assertNotFound();      // 404
$response->assertUnprocessable(); // 422

// ❌ Bad - generic method
$response->assertStatus(201);
$response->assertStatus(403);
```

### Using Factories

**Always use factories for test data**:

```php
// ✅ Good - using factories
$user = User::factory()->create();
$activity = Activity::factory()->for($user)->create();
$segment = Segment::factory()->create(['type' => SegmentType::Run]);

// ❌ Bad - manual creation
$user = User::create([
    'name' => 'Test User',
    'email' => 'test@test.com',
    'password' => bcrypt('password'),
    ...
]);
```

**Custom factory states**:

```php
// In ActivityFactory
public function withRoute(): static
{
    return $this->state(fn (array $attributes) => [
        'route' => DB::raw("ST_GeomFromEWKT('SRID=4326;LINESTRING(...)')"),
    ]);
}

// Usage
$activity = Activity::factory()->withRoute()->create();
```

### Datasets (for validation tests)

**Use datasets to avoid duplication**:

```php
it('validates required fields', function (string $field) {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/activities', [
        'type' => 'run',
        'title' => 'Test',
        $field => null,  // Missing field
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with(['type', 'title', 'started_at']);
```

### Running Tests

```bash
# All tests
php artisan test

# Specific file
php artisan test tests/Feature/Api/v1/Activity/ActivityCrudTest.php

# Filter by name
php artisan test --filter=ActivityTracking

# Stop on first failure
php artisan test --stop-on-failure

# Run only failed tests
php artisan test --failed
```

---

## Common Patterns

### Creating New Endpoint

**Step-by-step**:

1. **Create Data class** (if needed):
   ```bash
   php artisan make:class Data/Activity/ActivityData
   ```

2. **Create Controller**:
   ```bash
   php artisan make:controller Api/v1/Activity/ActivityController
   ```

3. **Add Route**:
   ```php
   // routes/api.php
   Route::apiResource('activities', ActivityController::class);
   ```

4. **Write Test**:
   ```bash
   php artisan make:test --pest Api/v1/Activity/ActivityCrudTest
   ```

5. **Update docs**:
   - Add to `docs/api.md`

### Creating New Service

```bash
php artisan make:class Services/Your/YourService
```

```php
<?php declare(strict_types=1);

namespace App\Services\Your;

class YourService
{
    public function __construct(
        private readonly DependencyService $dependency
    ) {}

    public function doSomething(Model $model): Result
    {
        // Business logic
        return new Result(...);
    }
}
```

**Register in AppServiceProvider** (if using interface):

```php
public function register(): void
{
    $this->app->singleton(YourServiceInterface::class, YourService::class);
}
```

### Creating Migration

```bash
php artisan make:migration create_your_table

# Or for changes:
php artisan make:migration add_column_to_your_table
```

**Always include `down()` method**:

```php
public function down(): void
{
    Schema::dropIfExists('your_table');
}
```

**For column changes, include ALL attributes** (Laravel limitation):

```php
// Modifying 'name' column
$table->string('name', 100)->change();  // ❌ Will drop nullable, index, etc!

// Correct way - include all attributes
$table->string('name', 100)->nullable()->unique()->change();
```

---

## Before Committing

**Run this checklist** (mandatory):

```bash
# 1. Format code
vendor/bin/pint

# 2. Check types
composer phpstan

# 3. Run tests
php artisan test

# 4. Security audit
composer audit

# All green? ✅ Ready to commit!
```

**Pre-commit hook** (optional but recommended):

Create `.git/hooks/pre-commit`:

```bash
#!/bin/sh

echo "Running pre-commit checks..."

# Format code
vendor/bin/pint --test
if [ $? -ne 0 ]; then
    echo "❌ Code formatting failed. Run: vendor/bin/pint"
    exit 1
fi

# Check types
composer phpstan --quiet
if [ $? -ne 0 ]; then
    echo "❌ PHPStan failed. Run: composer phpstan"
    exit 1
fi

# Run tests
php artisan test --parallel
if [ $? -ne 0 ]; then
    echo "❌ Tests failed. Run: php artisan test"
    exit 1
fi

echo "✅ All checks passed!"
exit 0
```

Make executable: `chmod +x .git/hooks/pre-commit`

---

## Code Review Guidelines

### As Author

**Before requesting review**:
- [ ] All checks passing (Pint, PHPStan, Tests)
- [ ] Self-review (read your own diff)
- [ ] Remove debug code (`dd()`, `dump()`, console.log, etc)
- [ ] Update documentation (if public API changes)
- [ ] Write clear PR description

**During review**:
- Keep PRs small (< 400 lines changed preferred)
- Respond to feedback promptly (within 1 business day)
- Don't take feedback personally (it's about the code, not you)
- Explain technical decisions when asked
- Be open to alternative approaches

### As Reviewer

**What to check**:
1. **Correctness**: Does it solve the problem?
2. **Tests**: Are edge cases covered?
3. **Performance**: Any obvious bottlenecks? (N+1 queries, unnecessary loops)
4. **Security**: Any SQL injection, XSS, or other vulnerabilities?
5. **Readability**: Is code easy to understand?
6. **Standards**: Follows our conventions?

**How to give feedback**:
- Be constructive (not critical)
- Explain the "why" in feedback ("This causes N+1 because...")
- Suggest alternatives ("Consider using eager loading instead")
- Approve if it's "good enough" (not perfect)
- Prioritize: Critical bugs > Architecture issues > Style preferences

**Response time**: Within 1 business day

**Example feedback**:

```markdown
# ✅ Good feedback
This causes an N+1 query when loading activities with users. Consider using eager loading:

```php
Activity::with('user')->get();
```

# ❌ Bad feedback
This is wrong.
```

---

## Performance Guidelines

### Database Performance

#### Avoid N+1 Queries

**Always use Telescope** to check for N+1 queries during development.

```php
// ❌ Bad - N+1 query
$activities = Activity::all();
foreach ($activities as $activity) {
    echo $activity->user->name;      // Query for each user
    echo $activity->likes->count();   // Query for each activity
}

// ✅ Good - single query
$activities = Activity::with('user', 'likes')->get();
foreach ($activities as $activity) {
    echo $activity->user->name;
    echo $activity->likes->count();
}
```

#### Use Indexes

**Create indexes for frequently queried columns**:

```php
// In migration
$table->index('user_id');
$table->index(['user_id', 'type']);  // Composite index
$table->unique('email');
```

**Check index usage** with `EXPLAIN ANALYZE`:

```sql
EXPLAIN ANALYZE
SELECT * FROM activities WHERE user_id = 1 AND type = 'run';
```

#### Limit Eager Loading

**Laravel 12** allows limiting eagerly loaded records:

```php
Activity::with([
    'likes' => fn($q) => $q->limit(10),
    'comments' => fn($q) => $q->latest()->limit(5)
])->get();
```

### Caching Strategy

**Cache expensive queries** (5 min TTL for feeds):

```php
Cache::remember("feed:user:{$userId}", 300, function () use ($userId) {
    return FeedService::getFollowingFeed($userId);
});
```

**Clear cache when data changes** (if needed):

```php
Cache::forget("feed:user:{$userId}");
```

### PostGIS Performance

**Always use spatial indexes** (GIST):

```php
// In migration
$table->geometry('route', 'linestring', 4326);
$table->spatialIndex('route');  // ✅ Critical for performance!
```

**Transform to projection for distance calculations**:

```sql
-- ✅ Good - use projection 3857 for meters
ST_Distance(
    ST_Transform(point1, 3857),
    ST_Transform(point2, 3857)
)

-- ❌ Bad - 4326 returns degrees (not useful)
ST_Distance(point1, point2)
```

**Use ST_DWithin instead of ST_Distance** (uses index):

```sql
-- ✅ Good - uses spatial index
WHERE ST_DWithin(route, point, 0.1)

-- ❌ Bad - doesn't use spatial index
WHERE ST_Distance(route, point) < 0.1
```

### Code-Level Optimization

1. **Use collections efficiently**: `pluck()`, `map()`, `filter()` instead of loops
2. **Avoid unnecessary queries**: Check if relationship exists before querying
3. **Use `chunk()` for large datasets**: Process in batches (1000 records at a time)
4. **Defer expensive operations**: Use queue jobs for background processing

---

## Questions?

**This guide unclear?** Open PR to improve it!

**Need more examples?** Check existing code for patterns!

**Stuck?** Ask in Slack: #backend-fittrack

---

**Last updated**: 2025-11-10 - FitTrack BR v1.0.0

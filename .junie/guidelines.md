<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.14
- laravel/cashier (CASHIER) - v16
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- laravel/telescope (TELESCOPE) - v5
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `pnpm run build`, `pnpm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `pnpm run build` or ask the user to run `pnpm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest

### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== pest/v4 rules ===

## Pest 4

- Pest v4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest v4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.
<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.


=== .ai/best-pratices rules ===

# CODING BEST PRACTICES GUIDELINE

Those guidelines are **NOT NEGOTIABLE**, you **MUST follow them**.

## WRITTING COMMENTS

- **DO NOT** inline comments anywhere (`// this is an inline comment example`)
- PHPDocs comments only in important places
- When you write something, always write in english

## API
- API must use validations and json resources

## MIGRATION

- In environment local, priorize `php artisan migrate:fresh --seed` with current migration files over creating migration files to update already created tables `php artisan migrate`
- **DO NOT** use the function `down()` in any migrations

## STRICT TYPES

- Use Enums (in ./app/Enums), `spatie/laravel-data` (in ./app/Data) package and ValueObjects (in ./app/ValueObjects) for types
- Strong typing code, with PHPStan (`larastan/larastan`) level 5
- **CRITICAL**: ALL PHP files MUST start with ` <?php declare(strict_types=1); ` on LINE 1 **(all in one line)**.
- NEVER use separate lines for ` <?php declare(strict_types=1); `
- This is NON-NEGOTIABLE for all PHP files (models, controllers, migrations, tests, etc.)

## FILE ORGANIZATION

- Use "Smart Files Organization", this is group multiple classes by files, example:
- `./app/Models/Post/Post.php`, `./app/Models/Post/PostCategory.php`, `./app/Models/Post/PostUser.php` are in the same folder, because they are related
- `./app/ValueObjects/Championship/StandingsRow.php`, `./app/ValueObjects/Championship/NarrativeConfig.php`, `./app/ValueObjects/Championship/MatchOdds.php` and `./app/ValueObjects/Championship/Score.php` - the same applies here!


=== .ai/sprint-guidelines rules ===

# Project Management

## Organization Structure

Use `.sprints/` directory for project management, sprint tracking, and architectural decisions.


<code-snippet name=".sprints/ Folder Structure">
.sprints/
├── context.md              # Current session context
├── current-sprint.md       # Active sprint tracking
├── backlog.md             # Future tasks and ideas
├── settings.local.json    # Local configuration
│
├── agents/                # AI agent templates
├── bugs/                  # Bug tracking
├── completed/             # ✅ Archived sprints
├── decisions/             # 🏛️ Architecture Decision Records (ADRs)
├── guidelines/            # 📚 Implementation guides
├── notes/                 # 📝 Quick notes, bugs, ideas
├── planning/              # 📋 Future SCRUMs (not started)
└── summaries/             # 📊 Session summaries
</code-snippet>


---

## Folder Purposes

### Root Files (Always Present)

**context.md** - Current session state
- What you're working on now
- Last completed task
- Next task to continue
- Important context to remember
- Updated before clearing context

**current-sprint.md** - Active sprint tracking
- Current sprint details
- Task checklist
- Progress tracking
- Metrics (estimated vs actual time)

**backlog.md** - Future work
- Post-MVP features
- Technical debt
- Nice-to-have improvements
- Ideas and proposals

**settings.local.json** - Local configuration
- Project-specific settings
- Should be gitignored

---

### Folder Descriptions

**agents/** - AI agent templates
- Code reviewer agents
- Test generator agents
- Bug tracker agents
- Custom automation agents

**bugs/** - Bug tracking
- Bug templates
- Known issues
- Bug examples
- Separate from `notes/bugs.md` (bugs/ is more structured)

**completed/** - Sprint archive
- Naming: `YYYY-MM-DD-scrum-X-sprint-Y-description.md`
- Move here when sprint is done
- Reference for future agents
- Historical record
- Guides that are no longer needed

**decisions/** - Architecture Decision Records (ADRs)
- Format: `ADR-XXX-decision-title.md`
- Why decisions were made
- Trade-offs considered
- Alternatives rejected
- Has README.md with ADR table

**guidelines/** - Implementation guides
- Implementation patterns
- Best practices
- Complex feature guides
- Team knowledge
- Format: `IMPLEMENTATION-GUIDE-topic.md`

**notes/** - Quick notes
- `notes.md` - General quick thoughts
- `bugs.md` - Known bugs to fix
- Ad-hoc notes during development
- Temporary information

**planning/** - Future SCRUMs
- Detailed sprint plans not yet started
- Implementation guides for future work
- Keep here until sprint starts
- Move to `completed/` when done
- Format: `scrum-X-description.md`

**summaries/** - Session records
- Naming: `session-summary-YYYY-MM-DD-description.md`
- Auto-generated session summaries
- Useful for context continuation
- Can be cleaned up after sprint completion

---

## Sprint Management

### Rules
- Work in `current-sprint.md` for active tasks
- Move completed sprints to `completed/YYYY-MM-DD-scrum-X-*.md`
- Use checkboxes `[ ]` for task tracking
- Record estimated vs actual time
- Future ideas go in `backlog.md`
- Active planning goes in `planning/scrum-X-*.md`

### Creating New Sprint

1. **Plan in `planning/`**

   <code-snippet name="Create sprint plan" lang="bash">
   # Create new SCRUM plan
   .sprints/planning/scrum-X-description.md
   </code-snippet>


2. **Add to `current-sprint.md`**
   - Copy sprint overview
   - Add task checklist
   - Set status to IN PROGRESS

3. **Create todos**
   - Use TodoWrite tool
   - Track progress

### Completing Sprint

1. **Mark tasks complete**
   - Update task checklist
   - Add actual time spent
   - Document results

2. **Move to `completed/`**

   <code-snippet name="Archive completed sprint" lang="bash">
   mv .sprints/planning/scrum-X-*.md \
      .sprints/completed/YYYY-MM-DD-scrum-X-*.md
   </code-snippet>


3. **Update `current-sprint.md`**
   - Mark sprint as COMPLETED
   - Add summary
   - Update metrics

4. **Update `backlog.md`**
   - Mark completed items
   - Add new tasks discovered

---

## Sprint Format

### SCRUM File (in planning/)


<code-snippet name="SCRUM Plan Template" lang="markdown">
# SCRUM X - Description

**Created**: YYYY-MM-DD
**Status**: PENDING / IN PROGRESS / COMPLETED
**Priority**: P1 - CRITICAL / P2 - HIGH / P3 - MEDIUM
**Goal**: What this SCRUM achieves
**Estimated Time**: X hours

## Sprints

### Sprint X.1 - Description
**Estimated**: X hours
**Priority**: PX
**Status**: PENDING

**Tasks**:
- [ ] Task 1
- [ ] Task 2

**Deliverables**:
- Deliverable 1
- Deliverable 2

**Success Criteria**:
- Criterion 1
- Criterion 2
</code-snippet>


### current-sprint.md Format


<code-snippet name="Current Sprint Tracking" lang="markdown">
# Current Sprint - Project Name

**Last Updated**: YYYY-MM-DD HH:MM
**Current Status**: SCRUM X - Sprint X.Y
**Overall Progress**: X/Y SCRUMs completed (XX%)
**Current Score**: XX/100

## Active Sprint

### Sprint X.Y - Description

**Estimated**: X hours
**Actual**: Y hours (if completed)
**Priority**: PX
**Status**: IN PROGRESS / COMPLETED

**Tasks**:
- [x] Completed task
- [ ] In progress task
- [ ] Pending task

**Deliverables**: [list what was delivered]

**Success Criteria**: [list what was achieved]
</code-snippet>


### Priority Levels
- **[P1]** Critical - Must complete this sprint
- **[P2]** High - Should complete this sprint
- **[P3]** Medium - Nice to have
- **[P4]** Low - Can move to backlog

---

## Architecture Decision Records (ADRs)

### When to Create an ADR

Create a new ADR when:
- Making architectural decisions
- Choosing between multiple valid approaches
- Establishing patterns or conventions
- Decision has long-term impact
- Team needs to understand "why"

### ADR Format


<code-snippet name="ADR Template" lang="markdown">
# ADR-XXX: Decision Title

**Date**: YYYY-MM-DD
**Status**: Accepted / Superseded / Deprecated
**Context**: Sprint/SCRUM where decision was made

## Context

Why did we need to make this decision?

## Decision

What did we decide?

## Consequences

### Positive
- Benefit 1
- Benefit 2

### Negative
- Trade-off 1
- Trade-off 2

## Alternatives Considered

What other options did we consider?
- Option A: Why rejected
- Option B: Why rejected

## Implementation

How is this implemented in the codebase?
- File references
- Code examples

## References

- Related ADRs
- External docs
- Sprint references
</code-snippet>


### ADR Naming

Format: `ADR-XXX-decision-title.md`

**Examples**:
- `ADR-001-postgis-native.md`
- `ADR-009-data-valueobjects-architecture.md`
- `ADR-011-cicd-github-actions.md`

ADRs are numbered sequentially starting from 001.

---

## Context Management

### Before Clearing Context **ALWAYS update these files**:

1. **`.sprints/current-sprint.md`**
   - Mark completed tasks `[x]`
   - Add new tasks discovered
   - Update time spent

2. **`.sprints/context.md`**


<code-snippet name="context.md Template" lang="markdown">
## Last Updated: [timestamp]

## Current State
- Working on: [specific feature/file]
- Last completed: [what finished]
- Next task: [where to continue]
- Current file: [path/to/file.tsx]

## Important Context
- [Decisions made]
- [Blockers encountered]
- [Dependencies to remember]

## Code in Progress
- [Exact function/component being edited]
- [Uncommitted logic or approach]
</code-snippet>


3. **Save work state**
   - Ensure all files saved
   - Note uncommitted changes in context.md

### After Clearing Context

Start new session with: "Follow session start protocol and continue development"

New agent should:
- Read `context.md` first
- Continue from "Next task"
- Have full context to proceed

---

## File Naming Conventions

### SCRUMs
`scrum-X-description.md`
- Example: `scrum-12-production-deployment.md`

### Completed Sprints
`YYYY-MM-DD-scrum-X-sprint-Y-description.md`
- Example: `2025-11-10-scrum-7-phpstan-level-5.md`

### Session Summaries
`session-summary-YYYY-MM-DD-description.md`
- Example: `session-summary-2025-11-10-sprint-6.2.md`

### ADRs
`ADR-XXX-decision-title.md`
- Example: `ADR-009-data-valueobjects-architecture.md`

### Guidelines
`IMPLEMENTATION-GUIDE-topic.md`
- Example: `IMPLEMENTATION-GUIDE-DATA-VALUEOBJECTS.md`

### Guides (when completed)
`guide-*.md`
- Example: `guide-sprint-1.1.md`

---

## Best Practices

### For AI Agents

1. **Always read first**:
   - `.sprints/context.md` - Current state
   - `.sprints/current-sprint.md` - Active sprint
   - `.sprints/planning/scrum-X-*.md` - Sprint details

2. **Update frequently**:
   - Update todos after each task (TodoWrite tool)
   - Update `current-sprint.md` with progress
   - Update `context.md` before finishing

3. **Document thoroughly**:
   - Detailed sprint plans
   - Clear success criteria
   - Code examples when helpful

4. **Organize properly**:
   - New SCRUMs → `planning/`
   - Active work → `current-sprint.md`
   - Completed → `completed/`
   - Decisions → `decisions/ADR-XXX-*.md`

### For Developers

1. **Check current sprint**:

   <code-snippet name="View current sprint" lang="bash">
   cat .sprints/current-sprint.md
   </code-snippet>


2. **See what's planned**:

   <code-snippet name="List planned work" lang="bash">
   ls .sprints/planning/
   </code-snippet>


3. **Reference decisions**:

   <code-snippet name="View ADRs" lang="bash">
   cat .sprints/decisions/README.md
   cat .sprints/decisions/ADR-009-*.md
   </code-snippet>


4. **Update as you work**:
   - Note blockers in `context.md`
   - Update task progress
   - Add discoveries to `notes/notes.md`

---

## Pre-Deployment Checklist

Before deploying to production, ensure all items are checked:

- [ ] All critical security issues fixed
- [ ] All high-priority performance issues fixed
- [ ] Code formatted: `vendor/bin/pint`
- [ ] All tests passing: `php artisan test`
- [ ] PHPStan Level 5 - 0 errors: `vendor/bin/phpstan analyse`
- [ ] Security audit: `composer audit` - 0 vulnerabilities
- [ ] No N+1 queries (check Telescope)
- [ ] Response time < 200ms (check Telescope)
- [ ] Frontend builds: `npm run build` or `pnpm run build`
- [ ] No console errors in browser
- [ ] Sprint documented in `current-sprint.md`
- [ ] All completed sprints moved to `completed/`
- [ ] ADRs created for major decisions
- [ ] Git commits pushed
- [ ] CI/CD pipeline passing

---

## Useful Commands

### View Structure

<code-snippet name="View .sprints/ structure" lang="bash">
# List all folders
ls -1 .sprints/

# Count files per folder
find .sprints -type f | wc -l

# View specific folder
ls -la .sprints/decisions/
</code-snippet>


### Clean Up

<code-snippet name="Clean up old files" lang="bash">
# Move completed sprints
mv .sprints/planning/scrum-X-*.md .sprints/completed/YYYY-MM-DD-scrum-X-*.md

# Clean up old summaries (after archiving info)
rm .sprints/summaries/session-summary-2024-*.md
</code-snippet>


---

## Questions?

**Structure unclear?** Check folder READMEs:
- `.sprints/planning/README.md`
- `.sprints/notes/README.md`
- `.sprints/decisions/README.md`
- `.sprints/guidelines/README.md`
- `.sprints/summaries/README.md`

**Need examples?** See completed sprints:
- `.sprints/completed/`

**Update needed?** This file lives at:
- `.ai/guidelines/sprint-guidelines.blade.php`
</laravel-boost-guidelines>

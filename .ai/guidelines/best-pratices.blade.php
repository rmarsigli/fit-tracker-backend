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
- **CRITICAL**: ALL PHP files MUST start with `@verbatim <?php declare(strict_types=1); @endverbatim` on LINE 1 **(all in one line)**.
- NEVER use separate lines for `@verbatim <?php declare(strict_types=1); @endverbatim`
- This is NON-NEGOTIABLE for all PHP files (models, controllers, migrations, tests, etc.)

## FILE ORGANIZATION

- Use "Smart Files Organization", this is group multiple classes by files, example:
- `./app/Models/Post/Post.php`, `./app/Models/Post/PostCategory.php`, `./app/Models/Post/PostUser.php` are in the same folder, because they are related
- `./app/ValueObjects/Championship/StandingsRow.php`, `./app/ValueObjects/Championship/NarrativeConfig.php`, `./app/ValueObjects/Championship/MatchOdds.php` and `./app/ValueObjects/Championship/Score.php` - the same applies here!

# CODING BEST PRACTICES GUIDELINE

- Strong typing code, with PHPStan (`larastan/larastan`) level 5
- **DO NOT** inline comments anywhere (`// this is an inline comment example`)
- Start PHP files with @verbatim<?php declare(strict_types=1);@endverbatim in line 1. Only don't do this when you really can't
- PHPDocs comments only in important places
- When you write something, always write in english
- Use Enums (in ./app/Enums), `spatie/laravel-data` (in ./app/Data) package and ValueObjects (in ./app/ValueObjects) for types
- API must follow the best Laravel practices. Not just using strong type, use validations and json resources
- Use "Smart Files Organization", this is group multiple classes by files, example:
- `./app/Models/Post/Post.php`, `./app/Models/Post/PostCategory.php`, `./app/Models/Post/PostUser.php` are in the same folder, because they are related
- `./app/ValueObjects/Championship/StandingsRow.php`, `./app/ValueObjects/Championship/NarrativeConfig.php`, `./app/ValueObjects/Championship/MatchOdds.php` and `./app/ValueObjects/Championship/Score.php` - the same applies here!

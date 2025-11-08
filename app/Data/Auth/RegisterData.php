<?php declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class RegisterData extends Data
{
    public function __construct(
        #[Required, Min(3)]
        public string $name,

        #[Required, Email, Unique('users', 'email')]
        public string $email,

        #[Required, Min(3), Unique('users', 'username')]
        public string $username,

        #[Required, Min(8), Confirmed]
        public string $password,
    ) {}
}

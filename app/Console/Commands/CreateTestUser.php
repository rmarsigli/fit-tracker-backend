<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    protected $signature = 'user:create-test {--name= : User name} {--email= : User email} {--username= : Username}';

    protected $description = 'Create a test user with password "secret" and return bearer token';

    public function handle(): int
    {
        $timestamp = now()->timestamp;

        $name = $this->option('name') ?? "Test User $timestamp";
        $email = $this->option('email') ?? "test$timestamp@fittrack.com";
        $username = $this->option('username') ?? "test$timestamp";

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'password' => Hash::make('secret'),
        ]);

        $token = $user->createToken('expo-app')->plainTextToken;

        $this->newLine();
        $this->info('✅ Test user created successfully!');
        $this->newLine();

        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $user->name],
                ['Email', $user->email],
                ['Username', $user->username],
                ['Password', 'secret'],
                ['Bearer Token', $token],
            ]
        );

        $this->newLine();
        $this->comment('💡 Copy the bearer token above to use in your Expo app!');
        $this->newLine();

        return self::SUCCESS;
    }
}

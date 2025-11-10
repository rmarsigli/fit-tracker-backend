<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ValidateConfig extends Command
{
    protected $signature = 'config:validate';

    protected $description = 'Validate that all required environment variables are set';

    public function handle(): int
    {
        $this->info('🔍 Validating configuration...');
        $this->newLine();

        $requiredVars = [
            'APP_KEY' => 'Application encryption key',
            'APP_ENV' => 'Application environment',
            'DB_CONNECTION' => 'Database connection type',
            'DB_HOST' => 'Database host',
            'DB_DATABASE' => 'Database name',
            'DB_USERNAME' => 'Database username',
            'REDIS_HOST' => 'Redis host',
            'REDIS_PORT' => 'Redis port',
        ];

        $productionOnlyVars = [
            'SENTRY_LARAVEL_DSN' => 'Sentry DSN for error tracking',
        ];

        $errors = [];
        $warnings = [];

        // Check required vars
        foreach ($requiredVars as $var => $description) {
            $value = env($var);

            if (empty($value)) {
                $errors[] = "❌ {$var} is not set ({$description})";
            } else {
                $this->components->info("{$var}: ✓");
            }
        }

        // Check production-only vars
        if (app()->environment('production')) {
            foreach ($productionOnlyVars as $var => $description) {
                $value = env($var);

                if (empty($value)) {
                    $errors[] = "❌ {$var} is not set ({$description}) - REQUIRED IN PRODUCTION";
                } else {
                    $this->components->info("{$var}: ✓");
                }
            }
        } else {
            foreach ($productionOnlyVars as $var => $description) {
                $value = env($var);

                if (empty($value)) {
                    $warnings[] = "⚠️  {$var} is not set ({$description}) - recommended for production";
                }
            }
        }

        // Validate APP_KEY format
        $appKey = env('APP_KEY');
        if ($appKey && ! str_starts_with($appKey, 'base64:')) {
            $errors[] = '❌ APP_KEY must start with "base64:"';
        }

        // Validate database connection
        try {
            \DB::connection()->getPdo();
            $this->components->info('Database connection: ✓');
        } catch (\Exception $e) {
            $errors[] = '❌ Database connection failed: '.$e->getMessage();
        }

        // Validate Redis connection
        try {
            \Illuminate\Support\Facades\Redis::connection()->ping();
            $this->components->info('Redis connection: ✓');
        } catch (\Exception $e) {
            $errors[] = '❌ Redis connection failed: '.$e->getMessage();
        }

        $this->newLine();

        // Display warnings
        if (! empty($warnings)) {
            $this->warn('Warnings:');
            foreach ($warnings as $warning) {
                $this->line($warning);
            }
            $this->newLine();
        }

        // Display errors
        if (! empty($errors)) {
            $this->error('Configuration errors found:');
            $this->newLine();
            foreach ($errors as $error) {
                $this->line($error);
            }
            $this->newLine();
            $this->error('❌ Configuration validation failed!');
            $this->line('Please check your .env file and fix the errors above.');

            return Command::FAILURE;
        }

        $this->components->success('✅ All configuration validated successfully!');

        return Command::SUCCESS;
    }
}

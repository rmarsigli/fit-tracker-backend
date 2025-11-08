<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();

            $table->decimal('distance_meters', 10, 2)->default(0);
            $table->integer('duration_seconds')->default(0);
            $table->integer('moving_time_seconds')->default(0);

            $table->decimal('elevation_gain', 8, 2)->default(0);
            $table->decimal('elevation_loss', 8, 2)->default(0);

            $table->decimal('avg_speed_kmh', 5, 2)->nullable();
            $table->decimal('max_speed_kmh', 5, 2)->nullable();

            $table->integer('avg_heart_rate')->nullable();
            $table->integer('max_heart_rate')->nullable();

            $table->integer('calories')->nullable();
            $table->integer('avg_cadence')->nullable();

            $table->jsonb('splits')->nullable();
            $table->jsonb('weather')->nullable();
            $table->jsonb('raw_data')->nullable();

            $table->string('visibility')->default('public');

            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'type', 'started_at']);
        });

        DB::statement('ALTER TABLE activities ADD COLUMN route GEOMETRY(LineString, 4326)');
        DB::statement('ALTER TABLE activities ADD COLUMN start_point GEOMETRY(Point, 4326)');
        DB::statement('ALTER TABLE activities ADD COLUMN end_point GEOMETRY(Point, 4326)');

        DB::statement('CREATE INDEX activities_route_idx ON activities USING GIST (route)');
        DB::statement('CREATE INDEX activities_start_point_idx ON activities USING GIST (start_point)');
        DB::statement('CREATE INDEX activities_end_point_idx ON activities USING GIST (end_point)');
    }
};

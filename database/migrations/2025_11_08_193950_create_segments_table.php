<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type');

            $table->decimal('distance_meters', 10, 2);
            $table->decimal('avg_grade_percent', 5, 2)->default(0);
            $table->decimal('max_grade_percent', 5, 2)->default(0);
            $table->decimal('elevation_gain', 8, 2)->default(0);

            $table->integer('total_attempts')->default(0);
            $table->integer('unique_athletes')->default(0);

            $table->string('city')->nullable();
            $table->string('state')->nullable();

            $table->boolean('is_hazardous')->default(false);

            $table->timestamps();

            $table->index(['type', 'city']);
        });

        DB::statement('ALTER TABLE segments ADD COLUMN route GEOMETRY(LineString, 4326)');
        DB::statement('ALTER TABLE segments ADD COLUMN start_point GEOMETRY(Point, 4326)');
        DB::statement('ALTER TABLE segments ADD COLUMN end_point GEOMETRY(Point, 4326)');

        DB::statement('CREATE INDEX segments_route_idx ON segments USING GIST (route)');
        DB::statement('CREATE INDEX segments_start_point_idx ON segments USING GIST (start_point)');
        DB::statement('CREATE INDEX segments_end_point_idx ON segments USING GIST (end_point)');
    }
};

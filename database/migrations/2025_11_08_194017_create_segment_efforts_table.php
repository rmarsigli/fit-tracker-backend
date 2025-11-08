<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segment_efforts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->integer('duration_seconds');
            $table->decimal('avg_speed_kmh', 5, 2)->nullable();
            $table->integer('avg_heart_rate')->nullable();

            $table->integer('rank_overall')->nullable();
            $table->integer('rank_age_group')->nullable();

            $table->boolean('is_kom')->default(false);
            $table->boolean('is_pr')->default(false);

            $table->timestamp('achieved_at');
            $table->timestamps();

            $table->index(['segment_id', 'duration_seconds']);
            $table->index(['user_id', 'segment_id', 'achieved_at']);
        });
    }
};

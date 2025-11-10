<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['distance', 'duration', 'elevation'])->default('distance');
            $table->decimal('goal_value', 10, 2);
            $table->string('goal_unit', 20);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_public')->default(true);
            $table->integer('max_participants')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_public', 'starts_at', 'ends_at']);
            $table->index('created_by');
        });
    }
};

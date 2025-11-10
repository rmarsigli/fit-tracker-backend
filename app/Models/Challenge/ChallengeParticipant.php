<?php

declare(strict_types=1);

namespace App\Models\Challenge;

use App\Models\User;
use Database\Factories\ChallengeParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeParticipant extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return ChallengeParticipantFactory::new();
    }

    protected $fillable = [
        'challenge_id',
        'user_id',
        'current_progress',
        'joined_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'current_progress' => 'decimal:2',
            'joined_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function progressPercentage(): float
    {
        $goal = (float) $this->challenge->goal_value;

        if ($goal <= 0) {
            return 0;
        }

        return min(100, ($this->current_progress / $goal) * 100);
    }
}

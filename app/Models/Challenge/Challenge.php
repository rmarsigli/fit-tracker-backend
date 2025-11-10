<?php

declare(strict_types=1);

namespace App\Models\Challenge;

use App\Enums\Challenge\ChallengeType;
use App\Models\User;
use Database\Factories\ChallengeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Challenge extends Model
{
    use HasFactory, LogsActivity;

    protected static function newFactory()
    {
        return ChallengeFactory::new();
    }

    protected $fillable = [
        'created_by',
        'name',
        'description',
        'type',
        'goal_value',
        'goal_unit',
        'starts_at',
        'ends_at',
        'is_public',
        'max_participants',
    ];

    protected function casts(): array
    {
        return [
            'type' => ChallengeType::class,
            'goal_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_public' => 'boolean',
            'max_participants' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ChallengeParticipant::class);
    }

    public function isActive(): bool
    {
        $now = now();

        return $this->starts_at <= $now && $this->ends_at >= $now;
    }

    public function isUpcoming(): bool
    {
        return $this->starts_at > now();
    }

    public function isEnded(): bool
    {
        return $this->ends_at < now();
    }

    public function isFull(): bool
    {
        if ($this->max_participants === null) {
            return false;
        }

        return $this->participants()->count() >= $this->max_participants;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'type', 'goal_value', 'starts_at', 'ends_at', 'is_public'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

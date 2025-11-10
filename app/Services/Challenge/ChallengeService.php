<?php

declare(strict_types=1);

namespace App\Services\Challenge;

use App\Enums\Challenge\ChallengeType;
use App\Models\Activity\Activity;
use App\Models\Challenge\Challenge;
use App\Models\Challenge\ChallengeParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ChallengeService
{
    public function joinChallenge(Challenge $challenge, User $user): ChallengeParticipant
    {
        if ($challenge->isFull()) {
            throw new \RuntimeException('Challenge is full');
        }

        if ($challenge->isEnded()) {
            throw new \RuntimeException('Challenge has already ended');
        }

        $existing = ChallengeParticipant::where('challenge_id', $challenge->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            throw new \RuntimeException('User is already participating in this challenge');
        }

        return ChallengeParticipant::create([
            'challenge_id' => $challenge->id,
            'user_id' => $user->id,
            'current_progress' => 0,
            'joined_at' => now(),
        ]);
    }

    public function leaveChallenge(Challenge $challenge, User $user): bool
    {
        $participant = ChallengeParticipant::where('challenge_id', $challenge->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $participant) {
            throw new \RuntimeException('User is not participating in this challenge');
        }

        if ($participant->isCompleted()) {
            throw new \RuntimeException('Cannot leave a completed challenge');
        }

        return $participant->delete();
    }

    public function updateProgress(ChallengeParticipant $participant, Activity $activity): void
    {
        $challenge = $participant->challenge;

        if ($challenge->isEnded()) {
            return;
        }

        $progressIncrement = match ($challenge->type) {
            ChallengeType::Distance => $activity->distance_meters / 1000,
            ChallengeType::Duration => $activity->moving_time_seconds / 3600,
            ChallengeType::Elevation => $activity->elevation_gain ?? 0,
        };

        $newProgress = $participant->current_progress + $progressIncrement;
        $participant->current_progress = $newProgress;

        if ($newProgress >= $challenge->goal_value && $participant->completed_at === null) {
            $participant->completed_at = now();
        }

        $participant->save();
    }

    public function getLeaderboard(Challenge $challenge, int $limit = 20): Collection
    {
        return ChallengeParticipant::query()
            ->where('challenge_id', $challenge->id)
            ->with('user:id,name,email')
            ->orderByDesc('current_progress')
            ->orderBy('updated_at')
            ->limit($limit)
            ->get();
    }

    public function getUserChallenges(User $user, ?string $status = null): Collection
    {
        $query = Challenge::query()
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->withCount('participants')
            ->with(['creator:id,name', 'participants' => fn ($q) => $q->where('user_id', $user->id)])
            ->orderByDesc('created_at');

        if ($status === 'active') {
            $query->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now());
        } elseif ($status === 'upcoming') {
            $query->where('starts_at', '>', now());
        } elseif ($status === 'ended') {
            $query->where('ends_at', '<', now());
        }

        return $query->get();
    }

    public function getAvailableChallenges(?User $user = null): Collection
    {
        $query = Challenge::query()
            ->where('is_public', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->withCount('participants')
            ->with('creator:id,name')
            ->orderByDesc('created_at');

        if ($user) {
            $query->whereDoesntHave('participants', fn ($q) => $q->where('user_id', $user->id));
        }

        return $query->get();
    }

    public function recalculateProgress(ChallengeParticipant $participant): void
    {
        $challenge = $participant->challenge;
        $user = $participant->user;

        $activities = Activity::query()
            ->where('user_id', $user->id)
            ->where('started_at', '>=', $challenge->starts_at)
            ->where('started_at', '<=', $challenge->ends_at)
            ->get();

        $totalProgress = $activities->sum(fn (Activity $activity) => match ($challenge->type) {
            ChallengeType::Distance => $activity->distance_meters / 1000,
            ChallengeType::Duration => $activity->moving_time_seconds / 3600,
            ChallengeType::Elevation => $activity->elevation_gain ?? 0,
        });

        $participant->current_progress = $totalProgress;

        if ($totalProgress >= $challenge->goal_value && $participant->completed_at === null) {
            $participant->completed_at = now();
        }

        $participant->save();
    }
}

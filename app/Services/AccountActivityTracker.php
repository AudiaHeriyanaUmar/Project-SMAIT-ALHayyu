<?php

namespace App\Services;

use App\Models\AccountActivitySession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AccountActivityTracker
{
    public const IDLE_TIMEOUT_MINUTES = 15;

    public function start(User $user, string $sessionId): void
    {
        $sessionHash = hash('sha256', $sessionId);
        $now = CarbonImmutable::now();

        DB::transaction(function () use ($user, $sessionHash, $now): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            AccountActivitySession::query()
                ->where('session_hash', $sessionHash)
                ->whereNull('ended_at')
                ->update(['ended_at' => $now, 'updated_at' => $now]);

            $this->createSession($user->id, $sessionHash, $now);
        });
    }

    public function record(User $user, string $sessionId): void
    {
        $this->recordSessionHash($user, hash('sha256', $sessionId), CarbonImmutable::now());
    }

    public function end(User $user, string $sessionId): void
    {
        $sessionHash = hash('sha256', $sessionId);
        $now = CarbonImmutable::now();

        DB::transaction(function () use ($user, $sessionHash, $now): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $this->recordSession($user->id, $sessionHash, $now);

            AccountActivitySession::query()
                ->where('user_id', $user->id)
                ->where('session_hash', $sessionHash)
                ->whereNull('ended_at')
                ->update(['ended_at' => $now, 'updated_at' => $now]);
        });
    }

    public function endAllForUser(User $user): void
    {
        $now = CarbonImmutable::now();
        $sessionHashes = AccountActivitySession::query()
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->pluck('session_hash')
            ->unique();

        foreach ($sessionHashes as $sessionHash) {
            DB::transaction(function () use ($user, $sessionHash, $now): void {
                User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

                $this->recordSession($user->id, $sessionHash, $now);

                AccountActivitySession::query()
                    ->where('user_id', $user->id)
                    ->where('session_hash', $sessionHash)
                    ->whereNull('ended_at')
                    ->update(['ended_at' => $now, 'updated_at' => $now]);
            });
        }
    }

    public function expireIdleSessions(): int
    {
        $cutoff = CarbonImmutable::now()->subMinutes(self::IDLE_TIMEOUT_MINUTES);
        $sessionIds = AccountActivitySession::query()
            ->whereNull('ended_at')
            ->where('last_activity_at', '<=', $cutoff)
            ->pluck('id');
        $expired = 0;

        foreach ($sessionIds as $sessionId) {
            $wasExpired = DB::transaction(function () use ($sessionId, $cutoff): bool {
                $sessionCandidate = AccountActivitySession::query()->find($sessionId);

                if (! $sessionCandidate) {
                    return false;
                }

                User::withTrashed()->whereKey($sessionCandidate->user_id)->lockForUpdate()->firstOrFail();
                $session = AccountActivitySession::query()
                    ->whereKey($sessionId)
                    ->whereNull('ended_at')
                    ->lockForUpdate()
                    ->first();

                if (! $session) {
                    return false;
                }

                $lastActivity = CarbonImmutable::parse($session->last_activity_at);

                if ($lastActivity->greaterThan($cutoff)) {
                    return false;
                }

                $endedAt = $lastActivity->addMinutes(self::IDLE_TIMEOUT_MINUTES);
                $this->addActiveTime($session, $lastActivity, $endedAt);
                $session->update([
                    'last_activity_at' => $endedAt,
                    'ended_at' => $endedAt,
                ]);

                return true;
            });

            $expired += (int) $wasExpired;
        }

        return $expired;
    }

    private function recordSessionHash(User $user, string $sessionHash, CarbonImmutable $now): void
    {
        DB::transaction(function () use ($user, $sessionHash, $now): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $this->recordSession($user->id, $sessionHash, $now);
        });
    }

    private function recordSession(int $userId, string $sessionHash, CarbonImmutable $now): void
    {
        $session = AccountActivitySession::query()
            ->where('user_id', $userId)
            ->where('session_hash', $sessionHash)
            ->whereNull('ended_at')
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if (! $session) {
            $this->createSession($userId, $sessionHash, $now);

            return;
        }

        $lastActivity = CarbonImmutable::parse($session->last_activity_at);
        $elapsedSeconds = max(0, $lastActivity->diffInSeconds($now, false));

        if ($elapsedSeconds === 0) {
            return;
        }

        if ($elapsedSeconds > self::IDLE_TIMEOUT_MINUTES * 60) {
            $activeUntil = $lastActivity->addMinutes(self::IDLE_TIMEOUT_MINUTES);
            $this->addActiveTime($session, $lastActivity, $activeUntil);
            $session->update([
                'last_activity_at' => $activeUntil,
                'ended_at' => $activeUntil,
            ]);

            $this->createSession($userId, $sessionHash, $now);

            return;
        }

        $this->addActiveTime($session, $lastActivity, $now);
        $session->update(['last_activity_at' => $now]);
    }

    private function createSession(int $userId, string $sessionHash, CarbonImmutable $now): void
    {
        AccountActivitySession::query()->create([
            'user_id' => $userId,
            'session_hash' => $sessionHash,
            'started_at' => $now,
            'last_activity_at' => $now,
            'active_seconds' => 0,
        ]);
    }

    private function addActiveTime(
        AccountActivitySession $session,
        CarbonImmutable $startedAt,
        CarbonImmutable $endedAt,
    ): void {
        $seconds = max(0, $startedAt->diffInSeconds($endedAt, false));

        if ($seconds === 0) {
            return;
        }

        $session->increment('active_seconds', $seconds);
        $cursor = $startedAt;

        while ($cursor->lessThan($endedAt)) {
            $nextDay = $cursor->startOfDay()->addDay();
            $segmentEnd = $endedAt->lessThan($nextDay) ? $endedAt : $nextDay;
            $segmentSeconds = $cursor->diffInSeconds($segmentEnd, false);
            $today = $cursor->toDateString();

            DB::table('account_activity_daily')->insertOrIgnore([
                'user_id' => $session->user_id,
                'activity_date' => $today,
                'active_seconds' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('account_activity_daily')
                ->where('user_id', $session->user_id)
                ->where('activity_date', $today)
                ->increment('active_seconds', $segmentSeconds, ['updated_at' => now()]);

            $cursor = $segmentEnd;
        }
    }
}

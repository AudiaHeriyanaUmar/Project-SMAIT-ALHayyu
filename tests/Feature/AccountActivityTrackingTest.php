<?php

namespace Tests\Feature;

use App\Models\AccountActivitySession;
use App\Models\User;
use App\Services\AccountActivityTracker;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountActivityTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_creates_an_activity_session(): void
    {
        $user = User::factory()->create(['role' => 'guru']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('guru.jurnal.index'));

        $activitySession = AccountActivitySession::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertNull($activitySession->ended_at);
    }

    public function test_ending_a_session_closes_it_and_records_its_final_active_time(): void
    {
        $user = User::factory()->create();
        $tracker = app(AccountActivityTracker::class);
        $sessionId = 'logout-session';

        CarbonImmutable::setTestNow('2026-10-07 09:00:00');
        Carbon::setTestNow('2026-10-07 09:00:00');
        $tracker->start($user, $sessionId);

        CarbonImmutable::setTestNow('2026-10-07 09:05:00');
        Carbon::setTestNow('2026-10-07 09:05:00');
        $tracker->end($user, $sessionId);

        $activitySession = AccountActivitySession::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertNotNull($activitySession->ended_at);
        $this->assertSame(300, $activitySession->active_seconds);
    }

    public function test_activity_time_is_limited_by_the_fifteen_minute_idle_timeout(): void
    {
        $user = User::factory()->create();
        $tracker = app(AccountActivityTracker::class);
        $sessionId = 'activity-session';

        CarbonImmutable::setTestNow('2026-10-07 09:00:00');
        Carbon::setTestNow('2026-10-07 09:00:00');
        $tracker->start($user, $sessionId);

        CarbonImmutable::setTestNow('2026-10-07 09:05:00');
        Carbon::setTestNow('2026-10-07 09:05:00');
        $tracker->record($user, $sessionId);

        CarbonImmutable::setTestNow('2026-10-07 09:40:00');
        Carbon::setTestNow('2026-10-07 09:40:00');
        $tracker->record($user, $sessionId);

        $this->assertDatabaseHas('account_activity_daily', [
            'user_id' => $user->id,
            'activity_date' => '2026-10-07',
            'active_seconds' => 1200,
        ]);
        $this->assertSame(2, AccountActivitySession::query()->where('user_id', $user->id)->count());

    }

    public function test_daily_active_time_is_split_at_midnight(): void
    {
        $user = User::factory()->create();
        $tracker = app(AccountActivityTracker::class);

        CarbonImmutable::setTestNow('2026-10-07 23:58:00');
        Carbon::setTestNow('2026-10-07 23:58:00');
        $tracker->start($user, 'midnight-session');

        CarbonImmutable::setTestNow('2026-10-08 00:03:00');
        Carbon::setTestNow('2026-10-08 00:03:00');
        $tracker->record($user, 'midnight-session');

        $dailyTotals = DB::table('account_activity_daily')
            ->where('user_id', $user->id)
            ->orderBy('activity_date')
            ->pluck('active_seconds', 'activity_date');

        $this->assertSame(120, (int) $dailyTotals['2026-10-07']);
        $this->assertSame(180, (int) $dailyTotals['2026-10-08']);

    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }
}

<?php

namespace Tests\Feature;

use App\Models\AccountActivitySession;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminAuditRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_page_is_admin_only_and_lists_recent_admin_activity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'guru']);
        AdminAuditLog::query()->create([
            'admin_id' => $admin->id,
            'action' => 'student.updated',
            'description' => 'Data roster diperbarui.',
        ]);

        $this->actingAs($teacher)->get(route('admin.audit.index'))->assertForbidden();
        $this->get(route('admin.backup.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.audit.index'))->assertOk()->assertSee('Data roster diperbarui.');
        $this->get(route('admin.backup.index'))->assertOk()->assertSee('Unduh backup SQL');
        $this->get(route('admin.backup.download'))->assertStatus(501);
    }

    public function test_retention_command_prunes_only_expired_audit_and_ended_session_details(): void
    {
        $this->travelTo(now()->startOfDay());
        $user = User::factory()->create();
        $oldLogId = DB::table('admin_audit_logs')->insertGetId([
            'admin_id' => $user->id,
            'action' => 'old',
            'description' => 'Older than retention.',
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);
        $recentLogId = DB::table('admin_audit_logs')->insertGetId([
            'admin_id' => $user->id,
            'action' => 'recent',
            'description' => 'Within retention.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $oldSessionId = $this->createSession($user->id, now()->subDays(15));
        $recentSessionId = $this->createSession($user->id, now()->subDays(2));
        $openSessionId = AccountActivitySession::query()->create([
            'user_id' => $user->id,
            'session_hash' => str_repeat('a', 64),
            'started_at' => now()->subDays(30),
            'last_activity_at' => now()->subMinutes(2),
            'active_seconds' => 120,
        ])->id;
        $idleSessionId = AccountActivitySession::query()->create([
            'user_id' => $user->id,
            'session_hash' => str_repeat('b', 64),
            'started_at' => now()->subMinutes(20),
            'last_activity_at' => now()->subMinutes(20),
            'active_seconds' => 0,
        ])->id;
        DB::table('account_activity_daily')->insert([
            'user_id' => $user->id,
            'activity_date' => now()->subDays(30)->toDateString(),
            'active_seconds' => 120,
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        $this->artisan('app:prune-audit-data')->assertExitCode(0);

        $this->assertDatabaseMissing('admin_audit_logs', ['id' => $oldLogId]);
        $this->assertDatabaseHas('admin_audit_logs', ['id' => $recentLogId]);
        $this->assertDatabaseMissing('account_activity_sessions', ['id' => $oldSessionId]);
        $this->assertDatabaseHas('account_activity_sessions', ['id' => $recentSessionId]);
        $this->assertDatabaseHas('account_activity_sessions', ['id' => $openSessionId]);
        $this->assertDatabaseHas('account_activity_sessions', [
            'id' => $idleSessionId,
            'active_seconds' => 900,
        ]);
        $this->assertDatabaseHas('account_activity_daily', ['user_id' => $user->id, 'active_seconds' => 120]);
    }

    private function createSession(int $userId, \Illuminate\Support\Carbon $endedAt): int
    {
        return AccountActivitySession::query()->create([
            'user_id' => $userId,
            'session_hash' => hash('sha256', (string) $endedAt),
            'started_at' => $endedAt->copy()->subHour(),
            'last_activity_at' => $endedAt,
            'ended_at' => $endedAt,
            'active_seconds' => 600,
        ])->id;
    }
}

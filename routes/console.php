<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\AccountActivitySession;
use App\Models\AdminAuditLog;
use App\Services\AccountActivityTracker;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:prune-audit-data', function () {
    $cutoff = now()->subDays(14);
    $expiredSessions = app(AccountActivityTracker::class)->expireIdleSessions();
    $auditDeleted = AdminAuditLog::query()->where('created_at', '<', $cutoff)->delete();
    $sessionsDeleted = AccountActivitySession::query()
        ->whereNotNull('ended_at')
        ->where('ended_at', '<', $cutoff)
        ->delete();

    $this->info("Expired {$expiredSessions} idle sessions; deleted {$auditDeleted} audit logs and {$sessionsDeleted} detailed activity sessions older than 14 days.");
})->purpose('Remove admin audit logs and detailed activity sessions older than 14 days');

Schedule::command('app:prune-audit-data')->dailyAt('02:00')->withoutOverlapping();

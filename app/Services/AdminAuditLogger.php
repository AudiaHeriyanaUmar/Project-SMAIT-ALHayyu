<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AdminAuditLogger
{
    public function record(
        User $admin,
        string $action,
        string $description,
        ?Model $subject = null,
        array $metadata = [],
    ): void {
        AdminAuditLog::query()->create([
            'admin_id' => $admin->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}

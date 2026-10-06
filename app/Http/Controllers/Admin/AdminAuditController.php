<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:80'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $logs = AdminAuditLog::query()
            ->with('admin')
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when($filters['q'] ?? null, function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('subject_type', 'like', "%{$search}%")
                        ->orWhere('subject_id', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.audit.index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => AdminAuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}

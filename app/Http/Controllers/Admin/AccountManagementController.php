<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountActivityTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountManagementController extends Controller
{
    private const ROLES = ['admin', 'guru', 'calon_siswa'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(self::ROLES)],
        ]);

        $accounts = User::query()
            ->when($filters['q'] ?? null, function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $userIds = $accounts->getCollection()->modelKeys();
        $weekStart = now()->subDays(6)->toDateString();
        $today = now()->toDateString();

        $activity = DB::table('account_activity_daily')
            ->whereIn('user_id', $userIds)
            ->where('activity_date', '>=', $weekStart)
            ->select('user_id')
            ->selectRaw('SUM(active_seconds) as week_seconds')
            ->selectRaw('SUM(CASE WHEN activity_date = ? THEN active_seconds ELSE 0 END) as today_seconds', [$today])
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $sessions = DB::table('account_activity_sessions')
            ->whereIn('user_id', $userIds)
            ->select('user_id')
            ->selectRaw('MAX(last_activity_at) as last_activity_at')
            ->selectRaw(
                'MAX(CASE WHEN ended_at IS NULL AND last_activity_at >= ? THEN 1 ELSE 0 END) as is_online',
                [now()->subMinutes(AccountActivityTracker::IDLE_TIMEOUT_MINUTES)],
            )
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        return view('admin.accounts.index', [
            'accounts' => $accounts,
            'filters' => $filters,
            'activity' => $activity,
            'sessions' => $sessions,
            'onlineCount' => $sessions->where('is_online', 1)->count(),
            'roleCounts' => User::query()
                ->select('role', DB::raw('COUNT(*) as total'))
                ->groupBy('role')
                ->pluck('total', 'role'),
        ]);
    }

    public function create(): View
    {
        return view('admin.accounts.form', [
            'account' => new User,
            'roles' => self::ROLES,
            'editing' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(self::ROLES)],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('admin.accounts.index')->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('admin.accounts.form', [
            'account' => $user,
            'roles' => self::ROLES,
            'editing' => true,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        $updated = DB::transaction(function () use ($request, $user, $validated): string {
            $adminIds = User::query()
                ->where('role', 'admin')
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');
            $user->refresh();

            if ($request->user()->is($user) && $validated['role'] !== 'admin') {
                return 'self';
            }

            if ($user->role === 'admin' && $validated['role'] !== 'admin' && $adminIds->count() <= 1) {
                return 'last_admin';
            }

            $user->update($validated);

            return 'updated';
        });

        if ($updated === 'self') {
            return back()->withErrors(['role' => 'Role admin pada akun Anda sendiri tidak dapat diubah.'])->withInput();
        }

        if ($updated === 'last_admin') {
            return back()->withErrors(['role' => 'Role akun admin terakhir tidak dapat diubah.'])->withInput();
        }

        return redirect()->route('admin.accounts.index')->with('success', 'Informasi akun berhasil diperbarui.');
    }

    public function editPassword(User $user): View
    {
        return view('admin.accounts.password', ['account' => $user]);
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return redirect()->route('admin.accounts.index')->with('success', "Password akun {$user->name} berhasil diubah.");
    }

    public function destroy(User $user, AccountActivityTracker $activityTracker): RedirectResponse
    {
        if (request()->user()->is($user)) {
            return back()->withErrors(['account' => 'Anda tidak dapat menghapus akun sendiri.']);
        }

        $canDelete = DB::transaction(function () use ($user, $activityTracker): bool {
            $adminIds = User::query()
                ->where('role', 'admin')
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            $user->refresh();

            if ($user->role === 'admin' && $adminIds->count() <= 1) {
                return false;
            }

            $activityTracker->endAllForUser($user);
            $user->delete();

            return true;
        });

        if (! $canDelete) {
            return back()->withErrors(['account' => 'Akun admin terakhir tidak dapat dihapus.']);
        }

        return redirect()->route('admin.accounts.index')->with('success', "Akun {$user->name} berhasil dihapus.");
    }
}

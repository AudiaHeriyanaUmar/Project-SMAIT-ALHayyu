<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JurnalGuru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Services\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.classes.index', [
            'classes' => Kelas::query()->with('waliKelas')->withCount('siswas')->orderBy('nama_kelas')->get(),
            'teachers' => User::query()->where('role', 'guru')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:255', 'unique:kelas,nama_kelas'],
            'wali_kelas_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'guru')->whereNull('deleted_at')),
            ],
        ]);
        $class = Kelas::query()->create($validated);
        $auditLogger->record($request->user(), 'class.created', "Kelas {$class->nama_kelas} dibuat.", $class, [
            'wali_kelas_id' => $class->wali_kelas_id,
        ]);

        return redirect()->route('admin.classes.index')->with('success', "Kelas {$class->nama_kelas} berhasil ditambahkan.");
    }

    public function update(Request $request, Kelas $class, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:255', Rule::unique('kelas', 'nama_kelas')->ignore($class->id)],
            'wali_kelas_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'guru')->whereNull('deleted_at')),
            ],
        ]);

        if (
            $validated['nama_kelas'] !== $class->nama_kelas
            && JurnalGuru::query()->where('kelas_id', $class->id)->exists()
        ) {
            return back()->withErrors([
                'nama_kelas' => 'Nama kelas tidak dapat diubah karena sudah tercatat pada jurnal historis.',
            ])->withInput();
        }

        $previous = $class->only(['nama_kelas', 'wali_kelas_id']);
        $class->update($validated);
        $auditLogger->record($request->user(), 'class.updated', "Data kelas {$class->nama_kelas} diperbarui.", $class, [
            'before' => $previous,
            'after' => $class->only(['nama_kelas', 'wali_kelas_id']),
        ]);

        return redirect()->route('admin.classes.index')->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function promote(Request $request, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'source_class_id' => ['required', 'integer', 'different:target_class_id', 'exists:kelas,id'],
            'target_class_id' => ['required', 'integer', 'exists:kelas,id'],
        ]);

        $moved = DB::transaction(function () use ($validated): int {
            $classes = Kelas::query()
                ->whereIn('id', [$validated['source_class_id'], $validated['target_class_id']])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $source = $classes->get((int) $validated['source_class_id']);
            $target = $classes->get((int) $validated['target_class_id']);

            if (! $source || ! $target) {
                abort(404);
            }

            if (Siswa::query()->where('kelas_id', $target->id)->exists()) {
                return -1;
            }

            $students = Siswa::query()->where('kelas_id', $source->id)->lockForUpdate()->get();

            if ($students->isEmpty()) {
                return 0;
            }

            Siswa::query()->whereIn('id', $students->modelKeys())->update([
                'kelas_id' => $target->id,
                'updated_at' => now(),
            ]);

            return $students->count();
        });

        if ($moved === -1) {
            return back()->withErrors([
                'target_class_id' => 'Kelas tujuan harus kosong agar siswa dari dua angkatan tidak tercampur.',
            ])->withInput();
        }

        if ($moved === 0) {
            return back()->withErrors(['source_class_id' => 'Tidak ada siswa aktif yang dapat dinaikkan dari kelas asal.'])
                ->withInput();
        }

        $source = Kelas::query()->findOrFail($validated['source_class_id']);
        $target = Kelas::query()->findOrFail($validated['target_class_id']);
        $auditLogger->record($request->user(), 'class.promoted', "{$moved} siswa dipindahkan dari {$source->nama_kelas} ke {$target->nama_kelas}.", $source, [
            'target_class_id' => $target->id,
            'students_moved' => $moved,
        ]);

        return redirect()->route('admin.classes.index')
            ->with('success', "{$moved} siswa berhasil dipindahkan. Jurnal dan absensi historis tetap terhubung ke kelas asal.");
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuruJournalDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_can_view_their_journal_dashboard_and_create_form(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)
            ->get(route('guru.jurnal.index'))
            ->assertOk()
            ->assertSee('Riwayat mengajar')
            ->assertViewHas('ringkasan');

        $this->get(route('guru.jurnal.create'))
            ->assertOk()
            ->assertSee('name="tanggal"', false)
            ->assertSee('name="jam_ke"', false)
            ->assertSee('name="kelas_id"', false)
            ->assertSee('name="mata_pelajaran_id"', false);
    }

    public function test_non_guru_users_cannot_access_guru_journal_pages(): void
    {
        $candidate = User::factory()->create(['role' => 'calon_siswa']);

        $this->actingAs($candidate)
            ->get(route('guru.jurnal.index'))
            ->assertForbidden();

        $this->get(route('guru.jurnal.create'))->assertForbidden();
    }
}

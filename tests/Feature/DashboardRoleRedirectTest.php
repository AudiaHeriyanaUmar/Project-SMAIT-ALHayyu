<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRoleRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_is_redirected_to_their_journal(): void
    {
        $user = User::factory()->create(['role' => 'guru']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('guru.jurnal.index'));
    }

    public function test_admin_is_redirected_to_journal_monitoring(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_calon_siswa_can_render_the_dashboard_without_redirecting(): void
    {
        $user = User::factory()->create(['role' => 'calon_siswa']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewIs('dashboard');
    }
}

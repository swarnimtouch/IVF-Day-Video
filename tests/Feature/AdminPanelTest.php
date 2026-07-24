<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_can_login_and_see_submission_listing(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email' => 'admin@example.com']);
        User::factory()->create(['name' => 'Aditi Sharma', 'employee_code' => 'MV1024', 'prefix' => 'Dr.', 'city' => 'Mumbai', 'video_status' => 'completed']);

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/admin');
        $this->get('/admin')->assertOk()->assertSee('Dr. Aditi Sharma')->assertSee('MV1024');
    }

    public function test_regular_user_cannot_access_admin_panel(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get('/admin')->assertRedirect('/admin/login');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_screen_renders(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('admin.auth.login.index');
    }

    public function test_admin_can_authenticate_with_username_and_password(): void
    {
        $user = User::factory()->create([
            'username' => 'testadmin',
            'email' => 'admin@example.test',
        ]);

        $this->get(route('login'));

        $this->post(route('login'), [
            'username' => 'testadmin',
            'password' => 'password',
        ])->assertRedirect(route('admin.main'));

        $this->assertAuthenticatedAs($user);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_profile_index_renders_for_user_with_admin_role(): void
    {
        Role::query()->firstOrCreate(
            ['name' => RolesEnum::ADMIN->value, 'guard_name' => 'web'],
        );

        $user = User::factory()->create([
            'username' => 'profileuser',
            'email' => 'profile@example.test',
        ]);
        $user->assignRole(RolesEnum::ADMIN->value);

        $this->actingAs($user)
            ->get(route('admin.profile'))
            ->assertOk()
            ->assertViewIs('admin.profile.index');
    }
}

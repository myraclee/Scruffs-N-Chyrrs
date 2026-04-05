<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthLoginSecuritySchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_contains_login_security_columns(): void
    {
        $columns = Schema::getColumnListing('users');

        $this->assertContains('login_attempts', $columns);
        $this->assertContains('lockout_until', $columns);
        $this->assertContains('is_locked', $columns);
        $this->assertContains('must_reset_password', $columns);
    }

    public function test_failed_login_increments_login_attempts_without_query_errors(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-password',
            'login_attempts' => 0,
            'is_locked' => false,
            'lockout_until' => null,
        ]);

        $response = $this
            ->from('/login')
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        $response
            ->assertStatus(302)
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['password']);

        $user->refresh();

        $this->assertSame(1, $user->login_attempts);
        $this->assertNull($user->lockout_until);
        $this->assertFalse($user->is_locked);
    }
}

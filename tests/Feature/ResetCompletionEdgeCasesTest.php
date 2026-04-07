<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResetCompletionEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_code_transitions_session_from_unlock_email_to_verified_reset_email(): void
    {
        $user = User::factory()->create([
            'is_locked' => true,
            'must_reset_password' => false,
            'login_attempts' => 5,
            'lockout_until' => null,
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'unlock-token',
            'code' => '123456',
            'created_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this
            ->withSession([
                'unlock_email' => $user->email,
                'reset_email' => 'stale-reset@example.com',
            ])
            ->post(route('enter-code.verify'), [
                'code' => '123456',
            ]);

        $response
            ->assertRedirect(route('new-password'))
            ->assertSessionMissing('unlock_email')
            ->assertSessionMissing('reset_email')
            ->assertSessionHas('verified_reset_email', $user->email);
    }

    public function test_reset_completion_deletes_token_and_clears_all_related_session_keys(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldPassword123A!'),
            'is_locked' => true,
            'must_reset_password' => true,
            'login_attempts' => 5,
            'lockout_until' => null,
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'reset-token',
            'code' => '777777',
            'created_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this
            ->withSession([
                'reset_email' => 'stale-reset@example.com',
                'unlock_email' => $user->email,
                'verified_reset_email' => $user->email,
            ])
            ->post(route('new-password.reset'), [
                'new_password' => 'newPassword123A!',
                'new_password_confirmation' => 'newPassword123A!',
            ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionMissing('reset_email')
            ->assertSessionMissing('unlock_email')
            ->assertSessionMissing('verified_reset_email');

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);

        $user->refresh();

        $this->assertFalse($user->is_locked);
        $this->assertFalse($user->must_reset_password);
        $this->assertSame(0, $user->login_attempts);
        $this->assertNull($user->lockout_until);
        $this->assertTrue(Hash::check('newPassword123A!', $user->password));
    }
}

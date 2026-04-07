<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordLockoutBlockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_user_cannot_request_forgot_password_code(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'is_locked' => true,
            'must_reset_password' => false,
            'login_attempts' => 5,
            'lockout_until' => null,
        ]);

        $response = $this
            ->from(route('reset-password'))
            ->post(route('reset-password.send'), [
                'email' => $user->email,
            ]);

        $response
            ->assertRedirect(route('reset-password'))
            ->assertSessionHasErrors(['email'])
            ->assertSessionMissing('reset_email');

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_must_reset_password_user_cannot_request_forgot_password_code(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'is_locked' => false,
            'must_reset_password' => true,
            'login_attempts' => 0,
            'lockout_until' => null,
        ]);

        $response = $this
            ->from(route('reset-password'))
            ->post(route('reset-password.send'), [
                'email' => $user->email,
            ]);

        $response
            ->assertRedirect(route('reset-password'))
            ->assertSessionHasErrors(['email'])
            ->assertSessionMissing('reset_email');

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_unlocked_user_can_still_request_forgot_password_code(): void
    {
        $user = User::factory()->create([
            'is_locked' => false,
            'must_reset_password' => false,
        ]);

        $response = $this
            ->from(route('reset-password'))
            ->post(route('reset-password.send'), [
                'email' => $user->email,
            ]);

        $response
            ->assertRedirect(route('enter-code'))
            ->assertSessionHas('reset_email', $user->email);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_reset_code_verification_is_blocked_if_user_becomes_locked_after_code_issued(): void
    {
        $user = User::factory()->create([
            'is_locked' => false,
            'must_reset_password' => false,
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'reset-token',
            'code' => '222222',
            'created_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $user->update([
            'is_locked' => true,
            'login_attempts' => 5,
            'lockout_until' => null,
        ]);

        $response = $this
            ->withSession(['reset_email' => $user->email])
            ->post(route('enter-code.verify'), [
                'code' => '222222',
            ]);

        $response
            ->assertRedirect(route('reset-password'))
            ->assertSessionHasErrors(['email'])
            ->assertSessionMissing('reset_email')
            ->assertSessionMissing('verified_reset_email');

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_permanent_lock_clears_existing_reset_token(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-password',
            'is_locked' => false,
            'must_reset_password' => false,
            'login_attempts' => 4,
            'lockout_until' => now()->subSecond(),
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'existing-token',
            'code' => '999999',
            'created_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this
            ->from('/login')
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['password'])
            ->assertSessionHas('show_unlock_option', true);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }
}

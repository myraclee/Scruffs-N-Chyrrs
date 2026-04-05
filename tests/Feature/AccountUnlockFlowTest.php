<?php

namespace Tests\Feature;

use App\Mail\PasswordResetCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountUnlockFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_customer_login_shows_unlock_option(): void
    {
        $user = User::factory()->create([
            'is_locked' => true,
            'login_attempts' => 5,
            'lockout_until' => null,
            'must_reset_password' => false,
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
    }

    public function test_locked_owner_login_shows_unlock_option(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
            'is_locked' => true,
            'login_attempts' => 5,
            'lockout_until' => null,
            'must_reset_password' => false,
        ]);

        $response = $this
            ->from('/owner/login')
            ->post(route('login.store'), [
                'email' => $owner->email,
                'password' => 'wrong-password',
            ]);

        $response
            ->assertRedirect('/owner/login')
            ->assertSessionHasErrors(['password'])
            ->assertSessionHas('show_unlock_option', true);
    }

    public function test_unlock_request_is_generic_for_unknown_email(): void
    {
        Mail::fake();

        $response = $this
            ->from('/login')
            ->post(route('account-unlock.send'), [
                'email' => 'missing-user@example.com',
            ]);

        $response
            ->assertRedirect(route('enter-code'))
            ->assertSessionHas('success', 'If your account is eligible, a verification code has been sent to your email.');

        Mail::assertNothingSent();
    }

    public function test_unlock_request_does_not_send_code_for_unlocked_user(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'is_locked' => false,
        ]);

        $response = $this
            ->from('/login')
            ->post(route('account-unlock.send'), [
                'email' => $user->email,
            ]);

        $response->assertRedirect(route('enter-code'));

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);

        Mail::assertNothingSent();
    }

    public function test_unlock_request_sends_code_for_locked_user(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'is_locked' => true,
            'login_attempts' => 5,
            'must_reset_password' => false,
        ]);

        $response = $this
            ->from('/login')
            ->post(route('account-unlock.send'), [
                'email' => $user->email,
            ]);

        $response
            ->assertRedirect(route('enter-code'))
            ->assertSessionHas('unlock_email', $user->email);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);

        Mail::assertSent(PasswordResetCode::class, function (PasswordResetCode $mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_unlock_request_sends_code_when_password_reset_is_required(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'is_locked' => false,
            'must_reset_password' => true,
        ]);

        $response = $this
            ->from('/login')
            ->post(route('account-unlock.send'), [
                'email' => $user->email,
            ]);

        $response
            ->assertRedirect(route('enter-code'))
            ->assertSessionHas('unlock_email', $user->email);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);

        Mail::assertSent(PasswordResetCode::class, function (PasswordResetCode $mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_unlock_code_verification_unlocks_and_requires_password_reset(): void
    {
        $user = User::factory()->create([
            'is_locked' => true,
            'login_attempts' => 5,
            'lockout_until' => null,
            'must_reset_password' => false,
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
            ])
            ->post(route('enter-code.verify'), [
                'code' => '123456',
            ]);

        $response
            ->assertRedirect(route('new-password'))
            ->assertSessionHas('verified_reset_email', $user->email);

        $user->refresh();

        $this->assertFalse($user->is_locked);
        $this->assertSame(0, $user->login_attempts);
        $this->assertNull($user->lockout_until);
        $this->assertTrue($user->must_reset_password);
    }

    public function test_login_is_blocked_when_password_reset_is_required(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123A!'),
            'is_locked' => false,
            'must_reset_password' => true,
        ]);

        $response = $this
            ->from('/login')
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password123A!',
            ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['password'])
            ->assertSessionHas('show_unlock_option', true);

        $this->assertGuest();
    }

    public function test_password_reset_clears_force_reset_gate_and_allows_login(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldPassword123A!'),
            'is_locked' => false,
            'must_reset_password' => true,
            'login_attempts' => 3,
            'lockout_until' => now()->addMinutes(1),
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'reset-token',
            'code' => '777777',
            'created_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $resetResponse = $this
            ->withSession([
                'verified_reset_email' => $user->email,
            ])
            ->post(route('new-password.reset'), [
                'new_password' => 'newPassword123A!',
                'new_password_confirmation' => 'newPassword123A!',
            ]);

        $resetResponse->assertRedirect(route('login'));

        $user->refresh();

        $this->assertFalse($user->must_reset_password);
        $this->assertFalse($user->is_locked);
        $this->assertSame(0, $user->login_attempts);
        $this->assertNull($user->lockout_until);

        $loginResponse = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'newPassword123A!',
        ]);

        $loginResponse->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }
}

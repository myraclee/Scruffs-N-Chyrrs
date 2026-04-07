<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginLockoutProgressiveFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_third_failed_attempt_starts_two_minute_temporary_lock(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
            'login_attempts' => 2,
            'lockout_until' => null,
            'is_locked' => false,
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
            ->assertSessionHas('lockout_until');

        $user->refresh();

        $this->assertSame(3, $user->login_attempts);
        $this->assertNotNull($user->lockout_until);
        $this->assertFalse($user->is_locked);
        $this->assertTrue($user->lockout_until->isFuture());
        $this->assertTrue($user->lockout_until->lessThanOrEqualTo(now()->addMinutes(2)));
    }

    public function test_fourth_failed_attempt_starts_five_minute_temporary_lock(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
            'login_attempts' => 3,
            'lockout_until' => now()->subSecond(),
            'is_locked' => false,
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
            ->assertSessionHas('lockout_until');

        $user->refresh();

        $this->assertSame(4, $user->login_attempts);
        $this->assertNotNull($user->lockout_until);
        $this->assertFalse($user->is_locked);
        $this->assertTrue($user->lockout_until->isFuture());
        $this->assertTrue($user->lockout_until->lessThanOrEqualTo(now()->addMinutes(5)));
    }

    public function test_fifth_failed_attempt_creates_permanent_lock_and_unlock_option(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
            'login_attempts' => 4,
            'lockout_until' => now()->subSecond(),
            'is_locked' => false,
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

        $user->refresh();

        $this->assertSame(5, $user->login_attempts);
        $this->assertTrue($user->is_locked);
        $this->assertNull($user->lockout_until);
    }

    public function test_temporary_lock_window_blocks_attempts_without_incrementing_count(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
            'login_attempts' => 3,
            'lockout_until' => now()->addSeconds(90),
            'is_locked' => false,
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
            ->assertSessionHas('lockout_until');

        $user->refresh();

        $this->assertSame(3, $user->login_attempts);
        $this->assertFalse($user->is_locked);
        $this->assertNotNull($user->lockout_until);
        $this->assertTrue($user->lockout_until->isFuture());
    }
}

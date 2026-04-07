<?php

namespace Tests\Feature;

use App\Mail\PasswordResetCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetDailyCapTest extends TestCase
{
    use RefreshDatabase;

    private const CAP_MESSAGE = 'Password reset can only be completed once per day. Please try again tomorrow.';

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_customer_forgot_password_request_is_blocked_after_same_day_reset_completion(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 7, 11, 0, 0, 'Asia/Manila'));

        $user = User::factory()->create([
            'user_type' => 'customer',
            'email' => 'daily.cap.customer@gmail.com',
            'password' => Hash::make('OldPassword123!'),
            'password_reset_completed_at' => now('Asia/Manila')->subHour(),
            'is_locked' => false,
            'must_reset_password' => false,
        ]);

        $response = $this
            ->from(route('reset-password'))
            ->post(route('reset-password.send'), [
                'email' => $user->email,
            ]);

        $response
            ->assertRedirect(route('reset-password'))
            ->assertSessionHasErrors(['email' => self::CAP_MESSAGE]);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_customer_unlock_request_is_blocked_after_same_day_reset_completion(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::create(2026, 4, 7, 12, 30, 0, 'Asia/Manila'));

        $user = User::factory()->create([
            'user_type' => 'customer',
            'email' => 'daily.cap.unlock@gmail.com',
            'password_reset_completed_at' => now('Asia/Manila')->subMinutes(15),
            'is_locked' => true,
            'must_reset_password' => false,
            'login_attempts' => 5,
            'lockout_until' => null,
        ]);

        $response = $this
            ->from('/login')
            ->post(route('account-unlock.send'), [
                'email' => $user->email,
            ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => self::CAP_MESSAGE]);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);

        Mail::assertNothingSent();
    }

    public function test_customer_can_request_again_after_asia_manila_midnight(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'user_type' => 'customer',
            'email' => 'daily.cap.nextday@gmail.com',
            'password_reset_completed_at' => Carbon::create(2026, 4, 7, 23, 55, 0, 'Asia/Manila'),
            'is_locked' => false,
            'must_reset_password' => false,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 4, 8, 0, 5, 0, 'Asia/Manila'));

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

        Mail::assertSent(PasswordResetCode::class);
    }

    public function test_owner_is_exempt_from_daily_cap(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::create(2026, 4, 7, 15, 0, 0, 'Asia/Manila'));

        $owner = User::factory()->create([
            'user_type' => 'owner',
            'email' => 'owner.exempt@gmail.com',
            'password_reset_completed_at' => now('Asia/Manila')->subMinutes(10),
            'is_locked' => false,
            'must_reset_password' => false,
        ]);

        $response = $this
            ->from(route('reset-password'))
            ->post(route('reset-password.send'), [
                'email' => $owner->email,
            ]);

        $response
            ->assertRedirect(route('enter-code'))
            ->assertSessionHas('reset_email', $owner->email);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $owner->email,
        ]);

        Mail::assertSent(PasswordResetCode::class);
    }

    public function test_customer_successful_reset_sets_completion_timestamp(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 9, 9, 10, 0, 'Asia/Manila'));

        $user = User::factory()->create([
            'user_type' => 'customer',
            'email' => 'set.completed@gmail.com',
            'password' => Hash::make('OldPassword123!'),
            'password_reset_completed_at' => null,
            'is_locked' => false,
            'must_reset_password' => false,
            'login_attempts' => 0,
            'lockout_until' => null,
        ]);

        $response = $this
            ->withSession([
                'verified_reset_email' => $user->email,
            ])
            ->post(route('new-password.reset'), [
                'new_password' => 'NewPassword123!',
                'new_password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertRedirect(route('login'));

        $user->refresh();
        $this->assertNotNull($user->password_reset_completed_at);
        $this->assertSame(
            now('Asia/Manila')->toDateString(),
            $user->password_reset_completed_at->copy()->timezone('Asia/Manila')->toDateString(),
        );
    }

    public function test_customer_can_make_multiple_requests_before_first_successful_reset_of_day(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 10, 0, 0, 'Asia/Manila'));

        $user = User::factory()->create([
            'user_type' => 'customer',
            'email' => 'multi.request@gmail.com',
            'password_reset_completed_at' => null,
            'is_locked' => false,
            'must_reset_password' => false,
        ]);

        $first = $this
            ->from(route('reset-password'))
            ->post(route('reset-password.send'), [
                'email' => $user->email,
            ]);

        $first->assertRedirect(route('enter-code'));

        $second = $this
            ->from(route('reset-password'))
            ->post(route('reset-password.send'), [
                'email' => $user->email,
            ]);

        $second->assertRedirect(route('enter-code'));

        Mail::assertSent(PasswordResetCode::class, 2);
    }
}

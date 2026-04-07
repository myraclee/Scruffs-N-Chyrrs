<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VerificationCodeEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_enter_code_routes_back_to_reset_when_no_flow_is_active(): void
    {
        $showResponse = $this->get(route('enter-code'));
        $showResponse->assertRedirect(route('reset-password'));

        $verifyResponse = $this->post(route('enter-code.verify'), [
            'code' => '123456',
        ]);

        $verifyResponse->assertRedirect(route('reset-password'));
    }

    public function test_invalid_unlock_code_keeps_user_locked(): void
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
            ->withSession(['unlock_email' => $user->email])
            ->post(route('enter-code.verify'), [
                'code' => '654321',
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHasErrors(['code']);

        $user->refresh();

        $this->assertTrue($user->is_locked);
        $this->assertFalse($user->must_reset_password);
        $this->assertSame(5, $user->login_attempts);
    }

    public function test_expired_unlock_code_is_rejected_and_state_is_unchanged(): void
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
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this
            ->withSession(['unlock_email' => $user->email])
            ->post(route('enter-code.verify'), [
                'code' => '123456',
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHasErrors(['code']);

        $user->refresh();

        $this->assertTrue($user->is_locked);
        $this->assertFalse($user->must_reset_password);
    }

    public function test_malformed_token_record_is_treated_as_invalid_code(): void
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
            'expires_at' => null,
        ]);

        $response = $this
            ->withSession(['unlock_email' => $user->email])
            ->post(route('enter-code.verify'), [
                'code' => '123456',
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHasErrors(['code']);

        $user->refresh();

        $this->assertTrue($user->is_locked);
        $this->assertFalse($user->must_reset_password);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcedEmailRemediationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_compliant_user_with_invalid_password_is_rejected_without_remediation_state(): void
    {
        $user = User::factory()->create([
            'email' => 'legacy.user@yahoo.com',
            'password' => Hash::make('ValidPassword123!'),
        ]);

        $response = $this->from('/login')->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['password'])
            ->assertSessionMissing('force_email_remediation')
            ->assertSessionMissing('email_remediation_user_id');

        $this->assertGuest();
    }

    public function test_non_compliant_user_with_valid_credentials_gets_forced_remediation_state(): void
    {
        $user = User::factory()->create([
            'email' => 'legacy.user@yahoo.com',
            'password' => Hash::make('ValidPassword123!'),
            'is_locked' => false,
            'must_reset_password' => false,
            'lockout_until' => null,
        ]);

        $response = $this->from('/login')->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'ValidPassword123!',
        ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHas('force_email_remediation', true)
            ->assertSessionHas('email_remediation_user_id', $user->id)
            ->assertSessionHas('email_remediation_value', strtolower($user->email));

        $this->assertGuest();
    }

    public function test_lockout_precedence_is_preserved_for_non_compliant_account(): void
    {
        $user = User::factory()->create([
            'email' => 'legacy.user@yahoo.com',
            'password' => Hash::make('ValidPassword123!'),
            'is_locked' => true,
            'must_reset_password' => false,
            'login_attempts' => 5,
            'lockout_until' => null,
        ]);

        $response = $this->from('/login')->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'ValidPassword123!',
        ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['password'])
            ->assertSessionHas('show_unlock_option', true)
            ->assertSessionMissing('force_email_remediation');
    }

    public function test_remediation_save_requires_active_session_state(): void
    {
        $response = $this->from('/login')->post(route('login.remediate-email'), [
            'email' => 'new.user@gmail.com',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_remediation_save_rejects_invalid_email_and_keeps_pending_state(): void
    {
        $user = User::factory()->create([
            'email' => 'legacy.user@yahoo.com',
            'password' => Hash::make('ValidPassword123!'),
            'is_locked' => false,
            'must_reset_password' => false,
            'lockout_until' => null,
        ]);

        $response = $this
            ->withSession([
                'email_remediation_user_id' => $user->id,
                'email_remediation_started_at' => now()->timestamp,
            ])
            ->from('/login')
            ->post(route('login.remediate-email'), [
                'email' => 'bad_domain@yahoo.com',
            ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email'])
            ->assertSessionHas('email_remediation_user_id', $user->id);

        $this->assertGuest();
    }

    public function test_remediation_save_requires_unique_email(): void
    {
        $user = User::factory()->create([
            'email' => 'legacy.user@yahoo.com',
            'password' => Hash::make('ValidPassword123!'),
            'is_locked' => false,
            'must_reset_password' => false,
            'lockout_until' => null,
        ]);

        $existing = User::factory()->create([
            'email' => 'taken.user@gmail.com',
        ]);

        $response = $this
            ->withSession([
                'email_remediation_user_id' => $user->id,
                'email_remediation_started_at' => now()->timestamp,
            ])
            ->from('/login')
            ->post(route('login.remediate-email'), [
                'email' => $existing->email,
            ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email'])
            ->assertSessionHas('email_remediation_user_id', $user->id);

        $this->assertGuest();
    }

    public function test_successful_remediation_updates_email_and_authenticates_customer(): void
    {
        $user = User::factory()->create([
            'email' => 'legacy.user@yahoo.com',
            'password' => Hash::make('ValidPassword123!'),
            'user_type' => 'customer',
            'is_locked' => false,
            'must_reset_password' => false,
            'lockout_until' => null,
        ]);

        $response = $this
            ->withSession([
                'email_remediation_user_id' => $user->id,
                'email_remediation_started_at' => now()->timestamp,
            ])
            ->post(route('login.remediate-email'), [
                'email' => 'new.email@GMAIL.COM',
            ]);

        $response->assertRedirect(route('home'));

        $user->refresh();
        $this->assertSame('new.email@gmail.com', $user->email);
        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_remediation_updates_email_and_authenticates_owner(): void
    {
        $owner = User::factory()->create([
            'email' => 'legacy.owner@yahoo.com',
            'password' => Hash::make('ValidPassword123!'),
            'user_type' => 'owner',
            'is_locked' => false,
            'must_reset_password' => false,
            'lockout_until' => null,
        ]);

        $response = $this
            ->withSession([
                'email_remediation_user_id' => $owner->id,
                'email_remediation_started_at' => now()->timestamp,
            ])
            ->post(route('login.remediate-email'), [
                'email' => 'owner.updated@ust.edu.ph',
            ]);

        $response->assertRedirect(route('owner.dashboard'));

        $owner->refresh();
        $this->assertSame('owner.updated@ust.edu.ph', $owner->email);
        $this->assertAuthenticatedAs($owner);
    }
}

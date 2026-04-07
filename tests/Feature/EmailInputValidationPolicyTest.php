<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmailInputValidationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_accepts_allowed_domain_and_normalizes_uppercase_email(): void
    {
        $response = $this->post(route('signup.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'alpha.user@GMAIL.COM',
            'contact_number' => '09171234567',
            'password' => 'ValidPassword123!',
            'password_confirmation' => 'ValidPassword123!',
        ]);

        $response->assertRedirect(route('owner.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'alpha.user@gmail.com',
        ]);
    }

    public function test_signup_rejects_disallowed_domain(): void
    {
        $response = $this->from(route('signup'))->post(route('signup.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'alpha.user@yahoo.com',
            'contact_number' => '09171234567',
            'password' => 'ValidPassword123!',
            'password_confirmation' => 'ValidPassword123!',
        ]);

        $response
            ->assertRedirect(route('signup'))
            ->assertSessionHasErrors(['email']);
    }

    public function test_signup_rejects_invalid_prefix_characters(): void
    {
        $response = $this->from(route('signup'))->post(route('signup.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'alpha_user@gmail.com',
            'contact_number' => '09171234567',
            'password' => 'ValidPassword123!',
            'password_confirmation' => 'ValidPassword123!',
        ]);

        $response
            ->assertRedirect(route('signup'))
            ->assertSessionHasErrors(['email']);
    }

    public function test_login_with_non_compliant_account_triggers_required_remediation_for_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'legacy.user@yahoo.com',
            'password' => Hash::make('ValidPassword123!'),
        ]);

        $response = $this->from('/login')->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'ValidPassword123!',
        ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHas('force_email_remediation', true)
            ->assertSessionHas('email_remediation_user_id', $user->id);

        $this->assertGuest();
    }

    public function test_reset_password_send_rejects_non_compliant_email_domain(): void
    {
        $user = User::factory()->create([
            'email' => 'legacy.user@yahoo.com',
        ]);

        $response = $this->from(route('reset-password'))->post(route('reset-password.send'), [
            'email' => $user->email,
        ]);

        $response
            ->assertRedirect(route('reset-password'))
            ->assertSessionHasErrors(['email']);
    }

    public function test_profile_update_rejects_non_compliant_domain_and_accepts_allowed_domain(): void
    {
        $user = User::factory()->create([
            'email' => 'profile.user@gmail.com',
        ]);

        $invalidResponse = $this
            ->actingAs($user)
            ->from(route('edit-profile'))
            ->post(route('update-profile'), [
                'first_name' => 'Profile',
                'last_name' => 'User',
                'email' => 'new.user@yahoo.com',
                'contact_number' => '09171234567',
            ]);

        $invalidResponse
            ->assertRedirect(route('edit-profile'))
            ->assertSessionHasErrors(['email']);

        $validResponse = $this
            ->actingAs($user)
            ->from(route('edit-profile'))
            ->post(route('update-profile'), [
                'first_name' => 'Profile',
                'last_name' => 'User',
                'email' => 'new.user@UST.EDU.PH',
                'contact_number' => '09171234567',
            ]);

        $validResponse->assertRedirect(route('account'));

        $user->refresh();
        $this->assertSame('new.user@ust.edu.ph', $user->email);
    }
}

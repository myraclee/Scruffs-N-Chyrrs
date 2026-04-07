<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignupFirstOwnerRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_signup_is_created_as_owner_and_redirected_to_owner_dashboard(): void
    {
        $payload = $this->signupPayload('first.owner@gmail.com');

        $response = $this->post(route('signup.store'), $payload);

        $response->assertRedirect(route('owner.dashboard'));
        $this->assertDatabaseHas('users', [
            'email' => 'first.owner@gmail.com',
            'user_type' => 'owner',
        ]);

        $createdUser = User::where('email', 'first.owner@gmail.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertAuthenticatedAs($createdUser);
    }

    public function test_signup_creates_customer_when_users_already_exist(): void
    {
        User::factory()->create([
            'email' => 'existing.user@gmail.com',
            'user_type' => 'owner',
        ]);

        $response = $this->post(route('signup.store'), $this->signupPayload('next.user@gmail.com'));

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('users', [
            'email' => 'next.user@gmail.com',
            'user_type' => 'customer',
        ]);
    }

    public function test_signup_cannot_force_owner_role_from_request_payload(): void
    {
        User::factory()->create([
            'email' => 'already.present@gmail.com',
            'user_type' => 'owner',
        ]);

        $payload = $this->signupPayload('spoof.attempt@gmail.com');
        $payload['user_type'] = 'owner';

        $response = $this->post(route('signup.store'), $payload);

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('users', [
            'email' => 'spoof.attempt@gmail.com',
            'user_type' => 'customer',
        ]);
    }

    public function test_existing_users_are_not_modified_when_new_signup_occurs(): void
    {
        $existingOwner = User::factory()->create([
            'email' => 'stable.owner@gmail.com',
            'user_type' => 'owner',
        ]);

        $this->post(route('signup.store'), $this->signupPayload('new.customer@gmail.com'));

        $existingOwner->refresh();
        $this->assertSame('owner', $existingOwner->user_type);
    }

    /**
     * @return array<string, string>
     */
    private function signupPayload(string $email): array
    {
        return [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'contact_number' => '09171234567',
            'password' => 'ValidPassword123!',
            'password_confirmation' => 'ValidPassword123!',
        ];
    }
}

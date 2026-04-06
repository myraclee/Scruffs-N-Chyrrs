<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutSecurityResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_response_applies_security_cleanup_headers(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('logout'));

        $response
            ->assertRedirect(route('home'))
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT')
            ->assertHeader('Clear-Site-Data', '"cache", "cookies", "storage"');

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);

        $this->assertGuest();
    }

    public function test_logout_blocks_access_to_authenticated_pages_after_sign_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->get(route('account'))->assertRedirect(route('login'));
        $this->get(route('customer.orders'))->assertRedirect(route('login'));
    }

    public function test_authenticated_pages_do_not_emit_logout_cleanup_headers(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account'))
            ->assertOk()
            ->assertHeaderMissing('Clear-Site-Data');
    }
}

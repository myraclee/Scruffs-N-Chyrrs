<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerDashboardPageRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_owner_dashboard_page_renders_with_owner_name_and_sections(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 7, 10, 0, 0));

        $owner = User::factory()->create([
            'user_type' => 'owner',
            'first_name' => 'Aaron',
            'last_name' => 'Tester',
        ]);

        $response = $this
            ->actingAs($owner)
            ->get('/owner/pages/dashboard');

        $response
            ->assertOk()
            ->assertSee('Weekly Report')
            ->assertSee('Weekly Sales')
            ->assertSee('Aaron Tester!');
    }

    public function test_non_owner_is_redirected_away_from_owner_dashboard_page(): void
    {
        $customer = User::factory()->create([
            'user_type' => 'customer',
        ]);

        $response = $this
            ->actingAs($customer)
            ->get('/owner/pages/dashboard');

        $response->assertRedirect(route('home'));
    }
}

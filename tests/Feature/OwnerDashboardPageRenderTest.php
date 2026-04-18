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
            ->assertSee('periodSelector')
            ->assertSee('salesPeriodSelector')
            ->assertDontSee('Applies to Report and Sales')
            ->assertSee('Aaron Tester!');
    }

    public function test_owner_dashboard_page_respects_period_query_parameter(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 10, 10, 0, 0));

        $owner = User::factory()->create([
            'user_type' => 'owner',
            'first_name' => 'Period',
            'last_name' => 'Owner',
        ]);

        $response = $this
            ->actingAs($owner)
            ->get('/owner/pages/dashboard?period=monthly');

        $response
            ->assertOk()
            ->assertSee('Monthly Report')
            ->assertSee('Monthly Sales');
    }

    public function test_owner_dashboard_page_respects_independent_report_and_sales_period_query_parameters(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 10, 10, 0, 0));

        $owner = User::factory()->create([
            'user_type' => 'owner',
            'first_name' => 'Split',
            'last_name' => 'Owner',
        ]);

        $response = $this
            ->actingAs($owner)
            ->get('/owner/pages/dashboard?report_period=daily&sales_period=yearly');

        $response
            ->assertOk()
            ->assertSee('Daily Report')
            ->assertSee('Yearly Sales');
    }

    public function test_owner_dashboard_page_falls_back_to_weekly_for_invalid_period_query_parameter(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 10, 10, 0, 0));

        $owner = User::factory()->create([
            'user_type' => 'owner',
            'first_name' => 'Fallback',
            'last_name' => 'Owner',
        ]);

        $response = $this
            ->actingAs($owner)
            ->get('/owner/pages/dashboard?report_period=invalid&sales_period=invalid');

        $response
            ->assertOk()
            ->assertSee('Weekly Report')
            ->assertSee('Weekly Sales');
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

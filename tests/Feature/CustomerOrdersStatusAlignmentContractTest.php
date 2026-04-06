<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrdersStatusAlignmentContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_orders_page_renders_my_orders_sections_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/account/orders');

        $response
            ->assertOk()
            ->assertSee('My Orders')
            ->assertSee('Current Orders')
            ->assertSee('Completed Orders');
    }

    public function test_orders_script_uses_semantic_status_chip_classes_without_inline_status_styles(): void
    {
        $script = file_get_contents(base_path('resources/js/customer/pages/view_orders.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('order_group_card', $script);
        $this->assertStringContainsString('order_group_header', $script);
        $this->assertStringContainsString('order_group_status_chip', $script);
        $this->assertStringContainsString('renderOrderGroups(currentOrdersContent, current', $script);
        $this->assertStringContainsString('renderOrderGroups(', $script);
        $this->assertStringContainsString('completedOrdersContent', $script);
        $this->assertStringNotContainsString('background:#f9f0ff;border:1px solid #682c7a', $script);
    }

    public function test_orders_styles_define_status_chip_alignment_and_one_line_rules(): void
    {
        $css = file_get_contents(base_path('resources/css/customer/view_orders.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('.order_group_header', $css);
        $this->assertStringContainsString('.order_group_header_actions', $css);
        $this->assertStringContainsString('.order_group_status_chip', $css);
        $this->assertStringContainsString('flex-wrap: wrap;', $css);
        $this->assertStringContainsString('justify-self: end;', $css);
        $this->assertStringContainsString('max-width: 100%;', $css);
        $this->assertStringContainsString('white-space: nowrap;', $css);
        $this->assertStringContainsString('font-size: 11px;', $css);
        $this->assertStringContainsString('@media (max-width: 768px)', $css);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr);', $css);
        $this->assertStringContainsString('font-size: 10px;', $css);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class OrderModalProductTotalLabelContractTest extends TestCase
{
    public function test_order_modal_uses_dynamic_total_label_placeholder(): void
    {
        $markup = file_get_contents(resource_path('views/customer/pages/order_modal.blade.php'));

        $this->assertNotFalse($markup);
        $this->assertStringContainsString('id="grandTotalLabelText"', $markup);
        $this->assertStringNotContainsString('FULL TOTAL', $markup);
    }

    public function test_order_modal_script_implements_dynamic_product_total_rule(): void
    {
        $script = file_get_contents(resource_path('js/customer/pages/order_modal.js'));

        $this->assertNotFalse($script);
        $this->assertStringContainsString('resolveGrandTotalLabel', $script);
        $this->assertStringContainsString('Cart Total', $script);
        $this->assertStringContainsString('grandTotalLabelText', $script);
    }
}

<?php

namespace Tests\Feature;

use App\Models\OrderTemplate;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderTemplateApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_200_when_product_template_exists(): void
    {
        $product = Product::create([
            'name' => 'Configured Product',
            'slug' => 'configured-product',
            'description' => 'Configured for order template contract testing.',
            'cover_image_path' => 'products/covers/configured.jpg',
        ]);

        OrderTemplate::create([
            'product_id' => $product->id,
        ]);

        $response = $this->getJson("/api/customer-orders/product/{$product->id}/template");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.product.id', $product->id);
    }

    public function test_returns_404_when_product_does_not_exist(): void
    {
        $response = $this->getJson('/api/customer-orders/product/999999/template');

        $response
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'product_not_found');
    }

    public function test_returns_404_when_product_has_no_order_template_configured(): void
    {
        $product = Product::create([
            'name' => 'No Template Product',
            'slug' => 'no-template-product',
            'description' => 'Used for contract testing.',
            'cover_image_path' => 'products/covers/no-template.jpg',
        ]);

        $response = $this->getJson("/api/customer-orders/product/{$product->id}/template");

        $response
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'template_not_configured');
    }
}

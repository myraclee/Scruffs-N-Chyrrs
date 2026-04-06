<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentManagementListApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_returns_success_envelope_without_server_error(): void
    {
        $response = $this->getJson('/api/products');

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertIsArray($response->json('data'));
    }

    public function test_order_templates_index_returns_success_envelope_without_server_error(): void
    {
        $response = $this->getJson('/api/order-templates');

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertIsArray($response->json('data'));
    }
}

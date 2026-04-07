<?php

namespace Tests\Feature;

use App\Models\RushFee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RushFeeStoreApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_rush_fee_with_timeframes(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $payload = [
            'label' => 'Below 3000',
            'min_price' => 100,
            'max_price' => 2999,
            'timeframes' => [
                [
                    'label' => '2 days',
                    'percentage' => 20,
                ],
                [
                    'label' => '5 days',
                    'percentage' => 10,
                ],
            ],
        ];

        $response = $this
            ->actingAs($owner)
            ->postJson('/api/rush-fees', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.label', 'Below 3000')
            ->assertJsonPath('data.timeframes.0.label', '2 days')
            ->assertJsonPath('data.timeframes.1.label', '5 days');

        $this->assertDatabaseHas('rush_fees', [
            'label' => 'Below 3000',
        ]);

        $this->assertDatabaseHas('rush_fee_timeframes', [
            'label' => '2 days',
            'percentage' => 20,
            'sort_order' => 0,
        ]);

        $this->assertDatabaseHas('rush_fee_timeframes', [
            'label' => '5 days',
            'percentage' => 10,
            'sort_order' => 1,
        ]);
    }

    public function test_create_without_image_url_is_accepted_for_current_owner_ui_contract(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $response = $this
            ->actingAs($owner)
            ->postJson('/api/rush-fees', [
                'label' => 'Above 3000',
                'min_price' => 3000,
                'max_price' => 5000,
                'timeframes' => [
                    [
                        'label' => '3 days',
                        'percentage' => 15,
                    ],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $rushFee = RushFee::firstWhere('label', 'Above 3000');

        $this->assertNotNull($rushFee);
        $this->assertSame('', (string) $rushFee->image_url);
    }

    public function test_create_with_image_url_persists_uploaded_image_reference(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $response = $this
            ->actingAs($owner)
            ->postJson('/api/rush-fees', [
                'label' => 'With Uploaded Image',
                'min_price' => 5001,
                'max_price' => 9000,
                'image_url' => '/storage/rush_fees/uploaded-image.png',
                'timeframes' => [
                    [
                        'label' => '1 day',
                        'percentage' => 25,
                    ],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.image_url', '/storage/rush_fees/uploaded-image.png');

        $this->assertDatabaseHas('rush_fees', [
            'label' => 'With Uploaded Image',
            'image_url' => '/storage/rush_fees/uploaded-image.png',
        ]);
    }

    public function test_store_returns_422_when_max_price_is_not_greater_than_min_price(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $response = $this
            ->actingAs($owner)
            ->postJson('/api/rush-fees', [
                'label' => 'Invalid Range',
                'min_price' => 1000,
                'max_price' => 1000,
                'timeframes' => [
                    [
                        'label' => '2 days',
                        'percentage' => 20,
                    ],
                ],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['max_price']);
    }

    public function test_owner_can_create_open_ended_rush_fee_with_null_max_price(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $response = $this
            ->actingAs($owner)
            ->postJson('/api/rush-fees', [
                'label' => 'Above 6000',
                'min_price' => 6000,
                'max_price' => null,
                'timeframes' => [
                    [
                        'label' => '24 hours',
                        'percentage' => 25,
                    ],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.max_price', null);

        $this->assertDatabaseHas('rush_fees', [
            'label' => 'Above 6000',
            'max_price' => null,
        ]);
    }

    public function test_store_returns_422_when_second_open_ended_range_is_created(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        RushFee::create([
            'label' => 'Existing Open Ended',
            'min_price' => 3000,
            'max_price' => null,
            'image_url' => '',
        ]);

        $response = $this
            ->actingAs($owner)
            ->postJson('/api/rush-fees', [
                'label' => 'Second Open Ended',
                'min_price' => 6000,
                'max_price' => null,
                'timeframes' => [
                    [
                        'label' => '48 hours',
                        'percentage' => 18,
                    ],
                ],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['max_price']);
    }

    public function test_store_returns_422_when_range_overlaps_existing_range(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        RushFee::create([
            'label' => 'Existing Range',
            'min_price' => 1000,
            'max_price' => 4000,
            'image_url' => '',
        ]);

        $response = $this
            ->actingAs($owner)
            ->postJson('/api/rush-fees', [
                'label' => 'Overlapping Range',
                'min_price' => 3500,
                'max_price' => 5000,
                'timeframes' => [
                    [
                        'label' => '72 hours',
                        'percentage' => 10,
                    ],
                ],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['max_price']);
    }

    public function test_owner_can_update_existing_range_to_open_ended_when_no_other_open_ended_exists(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $rushFee = RushFee::create([
            'label' => 'Finite Range',
            'min_price' => 1000,
            'max_price' => 3000,
            'image_url' => '',
        ]);

        $response = $this
            ->actingAs($owner)
            ->putJson("/api/rush-fees/{$rushFee->id}", [
                'max_price' => null,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.max_price', null);

        $this->assertDatabaseHas('rush_fees', [
            'id' => $rushFee->id,
            'max_price' => null,
        ]);
    }

    public function test_update_returns_422_when_price_range_overlaps_existing_range(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        RushFee::create([
            'label' => 'Base Range',
            'min_price' => 1000,
            'max_price' => 2500,
            'image_url' => '',
        ]);

        $editable = RushFee::create([
            'label' => 'Editable Range',
            'min_price' => 2600,
            'max_price' => 5000,
            'image_url' => '',
        ]);

        $response = $this
            ->actingAs($owner)
            ->putJson("/api/rush-fees/{$editable->id}", [
                'min_price' => 2400,
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['max_price']);
    }

    public function test_non_owner_cannot_create_rush_fee(): void
    {
        $customer = User::factory()->create([
            'user_type' => 'customer',
        ]);

        $response = $this
            ->actingAs($customer)
            ->postJson('/api/rush-fees', [
                'label' => 'Blocked',
                'min_price' => 100,
                'max_price' => 300,
                'timeframes' => [
                    [
                        'label' => '2 days',
                        'percentage' => 20,
                    ],
                ],
            ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized access.');
    }
}

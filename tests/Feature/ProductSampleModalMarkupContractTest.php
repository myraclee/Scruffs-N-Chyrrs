<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSampleModalMarkupContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_modal_keeps_counter_between_grid_and_actions(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $response = $this
            ->actingAs($owner)
            ->get('/owner/pages/content_management');

        $response->assertOk();

        $html = $response->getContent();

        $gridPos = strpos($html, 'id="sampleImageGrid"');
        $counterPos = strpos($html, 'id="sampleImageCounter"');
        $actionsPos = strpos($html, 'class="sample_image_actions"');

        $this->assertNotFalse($gridPos, 'sampleImageGrid was not found in content management markup.');
        $this->assertNotFalse($counterPos, 'sampleImageCounter was not found in content management markup.');
        $this->assertNotFalse($actionsPos, 'sample_image_actions was not found in content management markup.');

        $this->assertTrue(
            $gridPos < $counterPos,
            'Expected sample image counter to appear after the sample image grid in markup order.',
        );

        $this->assertTrue(
            $counterPos < $actionsPos,
            'Expected sample image counter to appear before sample image actions in markup order.',
        );
    }
}

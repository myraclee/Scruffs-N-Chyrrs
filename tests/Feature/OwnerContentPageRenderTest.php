<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerContentPageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_content_page_renders_successfully(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $response = $this
            ->actingAs($owner)
            ->get('/owner/pages/content_management');

        $response
            ->assertOk()
            ->assertSee('Content Management');
    }
}

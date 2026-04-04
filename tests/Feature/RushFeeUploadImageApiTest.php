<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RushFeeUploadImageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_rush_fee_image(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $response = $this
            ->actingAs($owner)
            ->post('/api/rush-fees/upload-image', [
                'image' => $this->fakeImage('rush-fee.png'),
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Rush fee image uploaded successfully');

        $imagePath = $response->json('data.image_path');
        $imageUrl = $response->json('data.image_url');

        $this->assertIsString($imagePath);
        $this->assertNotSame('', $imagePath);
        $this->assertSame('/storage/' . $imagePath, $imageUrl);
        $this->assertTrue(Storage::disk('public')->exists($imagePath));
    }

    public function test_upload_image_requires_image_file(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $response = $this
            ->actingAs($owner)
            ->post('/api/rush-fees/upload-image', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_non_owner_cannot_upload_rush_fee_image(): void
    {
        Storage::fake('public');

        $customer = User::factory()->create([
            'user_type' => 'customer',
        ]);

        $response = $this
            ->actingAs($customer)
            ->post('/api/rush-fees/upload-image', [
                'image' => $this->fakeImage('rush-fee-customer.png'),
            ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized access.');
    }

    private function fakeImage(string $filename): UploadedFile
    {
        $onePixelPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5fNqQAAAAASUVORK5CYII=',
        );

        return UploadedFile::fake()->createWithContent($filename, $onePixelPng);
    }
}

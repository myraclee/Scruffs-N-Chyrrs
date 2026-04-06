<?php

namespace Tests\Feature;

use App\Models\HomeImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeImagesDeferredSaveSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_keeps_selected_removes_others_and_appends_new_uploads(): void
    {
        Storage::fake('public');

        $imageA = HomeImage::create([
            'image_path' => 'home_images/a.png',
            'sort_order' => 0,
        ]);

        $imageB = HomeImage::create([
            'image_path' => 'home_images/b.png',
            'sort_order' => 1,
        ]);

        $imageC = HomeImage::create([
            'image_path' => 'home_images/c.png',
            'sort_order' => 2,
        ]);

        Storage::disk('public')->put($imageA->image_path, 'a');
        Storage::disk('public')->put($imageB->image_path, 'b');
        Storage::disk('public')->put($imageC->image_path, 'c');

        $response = $this->post('/api/home-images/sync', [
            'existing_image_ids' => [$imageC->id, $imageA->id],
            'images' => [
                $this->fakeImage('new-home-image.png'),
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');

        $this->assertDatabaseMissing('home_images', ['id' => $imageB->id]);
        $this->assertFalse(Storage::disk('public')->exists($imageB->image_path));

        $this->assertDatabaseHas('home_images', [
            'id' => $imageC->id,
            'sort_order' => 0,
        ]);

        $this->assertDatabaseHas('home_images', [
            'id' => $imageA->id,
            'sort_order' => 1,
        ]);

        $newImage = HomeImage::query()
            ->whereNotIn('id', [$imageA->id, $imageB->id, $imageC->id])
            ->firstOrFail();

        $this->assertSame(2, (int) $newImage->sort_order);
        $this->assertTrue(Storage::disk('public')->exists($newImage->image_path));
    }

    public function test_sync_with_empty_payload_fails_validation_and_keeps_existing_images(): void
    {
        Storage::fake('public');

        $imageA = HomeImage::create([
            'image_path' => 'home_images/remove-all-a.png',
            'sort_order' => 0,
        ]);

        $imageB = HomeImage::create([
            'image_path' => 'home_images/remove-all-b.png',
            'sort_order' => 1,
        ]);

        Storage::disk('public')->put($imageA->image_path, 'a');
        Storage::disk('public')->put($imageB->image_path, 'b');

        $response = $this->post('/api/home-images/sync', []);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonPath('errors.images.0', 'At least one home page image is required.');

        $this->assertDatabaseCount('home_images', 2);
        $this->assertTrue(Storage::disk('public')->exists($imageA->image_path));
        $this->assertTrue(Storage::disk('public')->exists($imageB->image_path));
    }

    private function fakeImage(string $filename): UploadedFile
    {
        $onePixelPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5fNqQAAAAASUVORK5CYII=',
        );

        return UploadedFile::fake()->createWithContent($filename, $onePixelPng);
    }
}

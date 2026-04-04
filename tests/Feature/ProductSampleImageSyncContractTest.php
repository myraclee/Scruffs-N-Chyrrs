<?php

namespace Tests\Feature;

use App\Models\ProductSample;
use App\Models\ProductSampleImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductSampleImageSyncContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_keeps_only_submitted_existing_image_ids(): void
    {
        Storage::fake('public');

        $sample = ProductSample::create([
            'name' => 'Sample A',
            'description' => 'Original',
        ]);

        $keepImage = ProductSampleImage::create([
            'product_sample_id' => $sample->id,
            'image_path' => "product_samples/{$sample->id}/keep.png",
            'sort_order' => 0,
        ]);

        $removeImage = ProductSampleImage::create([
            'product_sample_id' => $sample->id,
            'image_path' => "product_samples/{$sample->id}/remove.png",
            'sort_order' => 1,
        ]);

        Storage::disk('public')->put($keepImage->image_path, 'keep-image');
        Storage::disk('public')->put($removeImage->image_path, 'remove-image');

        $response = $this->post("/api/product-samples/{$sample->id}", [
            '_method' => 'PUT',
            'name' => 'Sample A Updated',
            'existing_image_ids' => [$keepImage->id],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.images');

        $this->assertDatabaseHas('product_sample_images', ['id' => $keepImage->id]);
        $this->assertDatabaseMissing('product_sample_images', ['id' => $removeImage->id]);

        $this->assertTrue(Storage::disk('public')->exists($keepImage->image_path));
        $this->assertFalse(Storage::disk('public')->exists($removeImage->image_path));
    }

    public function test_update_without_keep_list_removes_existing_and_adds_new_images(): void
    {
        Storage::fake('public');

        $sample = ProductSample::create([
            'name' => 'Sample B',
            'description' => 'Original',
        ]);

        $oldImageA = ProductSampleImage::create([
            'product_sample_id' => $sample->id,
            'image_path' => "product_samples/{$sample->id}/old-a.png",
            'sort_order' => 0,
        ]);

        $oldImageB = ProductSampleImage::create([
            'product_sample_id' => $sample->id,
            'image_path' => "product_samples/{$sample->id}/old-b.png",
            'sort_order' => 1,
        ]);

        Storage::disk('public')->put($oldImageA->image_path, 'old-a');
        Storage::disk('public')->put($oldImageB->image_path, 'old-b');

        $response = $this->post("/api/product-samples/{$sample->id}", [
            '_method' => 'PUT',
            'name' => 'Sample B Updated',
            'images' => [
                $this->fakeImage('new-image.png'),
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.images');

        $this->assertDatabaseMissing('product_sample_images', ['id' => $oldImageA->id]);
        $this->assertDatabaseMissing('product_sample_images', ['id' => $oldImageB->id]);

        $this->assertFalse(Storage::disk('public')->exists($oldImageA->image_path));
        $this->assertFalse(Storage::disk('public')->exists($oldImageB->image_path));

        $newImages = ProductSampleImage::query()
            ->where('product_sample_id', $sample->id)
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(1, $newImages);
        $this->assertSame(0, (int) $newImages[0]->sort_order);
        $this->assertTrue(Storage::disk('public')->exists($newImages[0]->image_path));
    }

    private function fakeImage(string $filename): UploadedFile
    {
        $onePixelPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5fNqQAAAAASUVORK5CYII=',
        );

        return UploadedFile::fake()->createWithContent($filename, $onePixelPng);
    }
}

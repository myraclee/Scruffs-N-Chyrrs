<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductNoteImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductNoteImagesPayloadContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_keeps_only_note_ids_passed_in_existing_note_image_ids(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Contract Product',
            'description' => 'Initial',
            'cover_image_path' => 'products/covers/contract-cover.jpg',
        ]);

        $keep = ProductNoteImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/notes/contract-keep.jpg',
            'sort_order' => 0,
        ]);

        $remove = ProductNoteImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/notes/contract-remove.jpg',
            'sort_order' => 1,
        ]);

        Storage::disk('public')->put($keep->image_path, 'keep');
        Storage::disk('public')->put($remove->image_path, 'remove');

        $response = $this->post("/api/products/{$product->id}", [
            '_method' => 'PUT',
            'name' => 'Contract Product Updated',
            'description' => 'Updated',
            'existing_note_image_ids' => [$keep->id],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.note_images');

        $this->assertDatabaseHas('product_note_images', ['id' => $keep->id]);
        $this->assertDatabaseMissing('product_note_images', ['id' => $remove->id]);
        $this->assertTrue(Storage::disk('public')->exists($keep->image_path));
        $this->assertFalse(Storage::disk('public')->exists($remove->image_path));
    }

    public function test_update_with_empty_existing_note_image_ids_array_removes_all_notes(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Empty Keep List Product',
            'description' => 'Initial',
            'cover_image_path' => 'products/covers/empty-keep-cover.jpg',
        ]);

        $note = ProductNoteImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/notes/empty-keep-note.jpg',
            'sort_order' => 0,
        ]);

        Storage::disk('public')->put($note->image_path, 'note');

        $response = $this->post("/api/products/{$product->id}", [
            '_method' => 'PUT',
            'name' => 'Empty Keep List Product Updated',
            'description' => 'Updated',
            'existing_note_image_ids' => [],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data.note_images');

        $this->assertDatabaseMissing('product_note_images', ['id' => $note->id]);
        $this->assertFalse(Storage::disk('public')->exists($note->image_path));
    }
}

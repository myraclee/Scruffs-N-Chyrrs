<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductNoteImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductNoteImagesRemovalTroubleshootTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_without_existing_note_ids_removes_all_existing_note_images(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Troubleshoot Product',
            'description' => 'Initial',
            'cover_image_path' => 'products/covers/troubleshoot-cover.jpg',
        ]);

        $noteA = ProductNoteImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/notes/old-note-a.jpg',
            'sort_order' => 0,
        ]);

        $noteB = ProductNoteImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/notes/old-note-b.jpg',
            'sort_order' => 1,
        ]);

        Storage::disk('public')->put($noteA->image_path, 'a');
        Storage::disk('public')->put($noteB->image_path, 'b');

        $response = $this->post("/api/products/{$product->id}", [
            '_method' => 'PUT',
            'name' => 'Troubleshoot Product Updated',
            'description' => 'Updated',
            // Intentionally omit existing_note_image_ids to simulate remove-all via UI.
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data.note_images');

        $this->assertDatabaseMissing('product_note_images', ['id' => $noteA->id]);
        $this->assertDatabaseMissing('product_note_images', ['id' => $noteB->id]);
        $this->assertFalse(Storage::disk('public')->exists($noteA->image_path));
        $this->assertFalse(Storage::disk('public')->exists($noteB->image_path));
    }

    public function test_update_remove_all_then_add_new_notes_restarts_sort_order_from_zero(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Sort Reset Product',
            'description' => 'Initial',
            'cover_image_path' => 'products/covers/sort-reset-cover.jpg',
        ]);

        $oldNoteA = ProductNoteImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/notes/old-sort-a.jpg',
            'sort_order' => 3,
        ]);

        $oldNoteB = ProductNoteImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/notes/old-sort-b.jpg',
            'sort_order' => 7,
        ]);

        Storage::disk('public')->put($oldNoteA->image_path, 'old-a');
        Storage::disk('public')->put($oldNoteB->image_path, 'old-b');

        $response = $this->post("/api/products/{$product->id}", [
            '_method' => 'PUT',
            'name' => 'Sort Reset Product Updated',
            'description' => 'Updated',
            'note_images' => [
                $this->fakeImage('new-note-1.png'),
                $this->fakeImage('new-note-2.png'),
            ],
            // Keep-list intentionally omitted; all old notes should be removed first.
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.note_images');

        $this->assertDatabaseMissing('product_note_images', ['id' => $oldNoteA->id]);
        $this->assertDatabaseMissing('product_note_images', ['id' => $oldNoteB->id]);
        $this->assertFalse(Storage::disk('public')->exists($oldNoteA->image_path));
        $this->assertFalse(Storage::disk('public')->exists($oldNoteB->image_path));

        $newNotes = ProductNoteImage::query()
            ->where('product_id', $product->id)
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(2, $newNotes);
        $this->assertSame([0, 1], $newNotes->pluck('sort_order')->all());

        foreach ($newNotes as $newNote) {
            $this->assertTrue(Storage::disk('public')->exists($newNote->image_path));
        }
    }

    private function fakeImage(string $filename): UploadedFile
    {
        $onePixelPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5fNqQAAAAASUVORK5CYII=',
        );

        return UploadedFile::fake()->createWithContent($filename, $onePixelPng);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductNoteImage;
use App\Models\ProductPriceImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductNoteImagesPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_product_persists_note_images_and_returns_them(): void
    {
        Storage::fake('public');

        $response = $this->post('/api/products', [
            'name' => 'Sticker Bundle',
            'description' => 'Test product with notes',
            'cover_image' => $this->fakeImage('cover.png'),
            'price_images' => [
                $this->fakeImage('price-1.png'),
            ],
            'note_images' => [
                $this->fakeImage('note-1.png'),
                $this->fakeImage('note-2.png'),
            ],
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.note_images');

        $product = Product::query()->firstOrFail();

        $this->assertDatabaseCount('product_note_images', 2);
        $this->assertDatabaseHas('product_note_images', [
            'product_id' => $product->id,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('product_note_images', [
            'product_id' => $product->id,
            'sort_order' => 1,
        ]);

        $savedNotes = $response->json('data.note_images');
        foreach ($savedNotes as $noteImage) {
            $this->assertTrue(Storage::disk('public')->exists($noteImage['image_path']));
        }
    }

    public function test_update_product_keeps_removed_and_new_note_images_in_sync(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Original Name',
            'description' => 'Original description',
            'cover_image_path' => 'products/covers/original-cover.jpg',
        ]);

        Storage::disk('public')->put('products/covers/original-cover.jpg', 'cover');

        $priceImage = ProductPriceImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/prices/original-price.jpg',
            'sort_order' => 0,
        ]);
        Storage::disk('public')->put('products/prices/original-price.jpg', 'price');

        $keptNote = ProductNoteImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/notes/kept-note.jpg',
            'sort_order' => 0,
        ]);
        $removedNote = ProductNoteImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/notes/removed-note.jpg',
            'sort_order' => 1,
        ]);

        Storage::disk('public')->put('products/notes/kept-note.jpg', 'keep');
        Storage::disk('public')->put('products/notes/removed-note.jpg', 'remove');

        $response = $this->post("/api/products/{$product->id}", [
            '_method' => 'PUT',
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'existing_price_image_ids' => [$priceImage->id],
            'existing_note_image_ids' => [$keptNote->id],
            'note_images' => [
                $this->fakeImage('new-note.png'),
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('product_note_images', [
            'id' => $keptNote->id,
            'product_id' => $product->id,
        ]);

        $this->assertDatabaseMissing('product_note_images', [
            'id' => $removedNote->id,
        ]);

        $this->assertSame(2, ProductNoteImage::query()->where('product_id', $product->id)->count());
        $this->assertFalse(Storage::disk('public')->exists('products/notes/removed-note.jpg'));

        $savedNotes = $response->json('data.note_images');
        $this->assertCount(2, $savedNotes);

        foreach ($savedNotes as $noteImage) {
            $this->assertTrue(Storage::disk('public')->exists($noteImage['image_path']));
        }
    }

    public function test_customer_product_detail_payload_contains_note_images(): void
    {
        $product = Product::create([
            'name' => 'Detail Page Product',
            'description' => 'Detail page payload test',
            'cover_image_path' => 'products/covers/detail-cover.jpg',
        ]);

        ProductNoteImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/notes/detail-note.jpg',
            'sort_order' => 0,
        ]);

        $response = $this->get("/products/{$product->slug}");

        $response->assertOk();

        $html = $response->getContent();

        preg_match('/data-product="([^"]+)"/', $html, $matches);
        $this->assertNotEmpty($matches, 'data-product attribute should exist on product detail page.');

        $decoded = json_decode(htmlspecialchars_decode($matches[1], ENT_QUOTES), true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('note_images', $decoded);
        $this->assertCount(1, $decoded['note_images']);
    }

    private function fakeImage(string $filename): UploadedFile
    {
        $onePixelPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5fNqQAAAAASUVORK5CYII=',
        );

        return UploadedFile::fake()->createWithContent($filename, $onePixelPng);
    }
}

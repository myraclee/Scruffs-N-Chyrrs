<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductNoteImage;
use App\Models\ProductPriceImage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Get all products with their related images.
     * GET /api/products
     */
    public function index(): JsonResponse
    {
        try {
            $products = Product::with([
                'priceImages',
                'noteImages',
                'orderTemplate.options.optionTypes',
            ])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new product with cover image, price images, and note images.
     * POST /api/products
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'cover_image' => 'required|image|mimes:png,jpg,jpeg|max:5120',
                'price_images' => 'required|array|min:1',
                'price_images.*' => 'required|image|mimes:png,jpg,jpeg|max:5120',
                'note_images' => 'nullable|array',
                'note_images.*' => 'required|image|mimes:png,jpg,jpeg|max:5120',
            ]);

            // Store cover image
            $coverPath = $request->file('cover_image')->store('products/covers', 'public');

            // Create product
            $product = Product::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'cover_image_path' => $coverPath,
            ]);

            // Store price images
            foreach ($request->file('price_images') as $index => $image) {
                $path = $image->store('products/prices', 'public');
                ProductPriceImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'sort_order' => $index,
                ]);
            }

            // Store note images
            if ($request->hasFile('note_images')) {
                foreach ($request->file('note_images') as $index => $image) {
                    $path = $image->store('products/notes', 'public');
                    ProductNoteImage::create([
                        'product_id' => $product->id,
                        'image_path' => $path,
                        'sort_order' => $index,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product->load(['priceImages', 'noteImages']),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single product with its related images.
     * GET /api/products/{productId}
     */
    public function show($productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);

            return response()->json([
                'success' => true,
                'data' => $product->load([
                    'priceImages',
                    'noteImages',
                    'orderTemplate.options.optionTypes',
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a product.
     * PUT /api/products/{productId}
     */
    public function update(Request $request, $productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'cover_image' => 'nullable|image|mimes:png,jpg,jpeg|max:5120',
                'price_images' => 'nullable|array',
                'price_images.*' => 'required|image|mimes:png,jpg,jpeg|max:5120',
                'existing_price_image_ids' => 'nullable|array',
                'existing_price_image_ids.*' => 'integer',
                'note_images' => 'nullable|array',
                'note_images.*' => 'required|image|mimes:png,jpg,jpeg|max:5120',
                'existing_note_image_ids' => 'nullable|array',
                'existing_note_image_ids.*' => 'integer',
            ]);

            if ($request->hasFile('cover_image')) {
                // Delete old image if exists
                if ($product->cover_image_path) {
                    Storage::disk('public')->delete($product->cover_image_path);
                }
                $coverPath = $request->file('cover_image')->store('products/covers', 'public');
                $validated['cover_image_path'] = $coverPath;
            }

            $product->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'cover_image_path' => $validated['cover_image_path'] ?? $product->cover_image_path,
            ]);

            // Handle selective price image management
            // Get list of image IDs to keep (from frontend)
            $existingKeptIds = $request->input('existing_price_image_ids', []);
            $existingKeptIds = array_map('intval', $existingKeptIds);  // Ensure all are integers

            // Delete price images that are NOT in the keep list
            foreach ($product->priceImages as $priceImage) {
                if (!in_array($priceImage->id, $existingKeptIds)) {
                    Storage::disk('public')->delete($priceImage->image_path);
                    $priceImage->delete();
                }
            }

            // Create new price images if provided
            if ($request->hasFile('price_images')) {
                // Get the current max sort_order to continue sequence
                $maxSortOrder = $product->priceImages()
                    ->max('sort_order') ?? -1;

                foreach ($request->file('price_images') as $index => $image) {
                    $path = $image->store('products/prices', 'public');
                    ProductPriceImage::create([
                        'product_id' => $product->id,
                        'image_path' => $path,
                        'sort_order' => $maxSortOrder + $index + 1,
                    ]);
                }
            }

            // Always sync note images with the submitted keep-list.
            // If the keep-list is empty/missing, all existing notes are removed.
            $existingKeptNoteIds = $request->input('existing_note_image_ids', []);
            if (!is_array($existingKeptNoteIds)) {
                $existingKeptNoteIds = [$existingKeptNoteIds];
            }
            $existingKeptNoteIds = array_map('intval', $existingKeptNoteIds);

            foreach ($product->noteImages as $noteImage) {
                if (!in_array($noteImage->id, $existingKeptNoteIds)) {
                    Storage::disk('public')->delete($noteImage->image_path);
                    $noteImage->delete();
                }
            }

            // Create new note images if provided
            if ($request->hasFile('note_images')) {
                $maxNoteSortOrder = $product->noteImages()
                    ->max('sort_order') ?? -1;

                foreach ($request->file('note_images') as $index => $image) {
                    $path = $image->store('products/notes', 'public');
                    ProductNoteImage::create([
                        'product_id' => $product->id,
                        'image_path' => $path,
                        'sort_order' => $maxNoteSortOrder + $index + 1,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->load(['priceImages', 'noteImages']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a product and all its associated images.
     * DELETE /api/products/{productId}
     */
    public function destroy($productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            // Delete cover image
            if ($product->cover_image_path) {
                Storage::disk('public')->delete($product->cover_image_path);
            }

            // Delete all price images
            foreach ($product->priceImages as $priceImage) {
                Storage::disk('public')->delete($priceImage->image_path);
            }

            // Delete all note images
            foreach ($product->noteImages as $noteImage) {
                Storage::disk('public')->delete($noteImage->image_path);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

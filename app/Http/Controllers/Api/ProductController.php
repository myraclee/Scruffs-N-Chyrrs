<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPriceImage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Get all products with their price images.
     * GET /api/products
     */
    public function index(): JsonResponse
    {
        try {
            $products = Product::with('priceImages')
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
     * Create a new product with cover image and price images.
     * POST /api/products
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'cover_image' => 'required|image|mimes:png,jpg,jpeg|max:2048',
                'price_images' => 'required|array|min:1',
                'price_images.*' => 'required|image|mimes:png,jpg,jpeg|max:5120',
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

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product->load('priceImages'),
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
     * Get a single product with its price images.
     * GET /api/products/{id}
     */
    public function show(Product $product): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $product->load('priceImages'),
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
     * PUT /api/products/{id}
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'cover_image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
                'price_images' => 'nullable|array',
                'price_images.*' => 'required|image|mimes:png,jpg,jpeg|max:5120',
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

            // Handle price images if provided
            if ($request->hasFile('price_images')) {
                // Delete old price images from storage and database
                foreach ($product->priceImages as $priceImage) {
                    Storage::disk('public')->delete($priceImage->image_path);
                    $priceImage->delete();
                }

                // Store new price images
                foreach ($request->file('price_images') as $index => $image) {
                    $path = $image->store('products/prices', 'public');
                    ProductPriceImage::create([
                        'product_id' => $product->id,
                        'image_path' => $path,
                        'sort_order' => $index,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->load('priceImages'),
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
     * DELETE /api/products/{id}
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            // Delete cover image
            if ($product->cover_image_path) {
                Storage::disk('public')->delete($product->cover_image_path);
            }

            // Delete all price images
            foreach ($product->priceImages as $priceImage) {
                Storage::disk('public')->delete($priceImage->image_path);
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

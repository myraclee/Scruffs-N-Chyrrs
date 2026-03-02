<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductSample;
use App\Models\ProductSampleImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductSampleController extends Controller
{
    /**
     * Get all product samples with their images
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $samples = ProductSample::with('images')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $samples,
                'message' => 'Product samples retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve product samples',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single product sample with its images
     * 
     * @param \App\Models\ProductSample $productSample
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ProductSample $productSample)
    {
        try {
            $productSample->load('images');
            
            return response()->json([
                'success' => true,
                'data' => $productSample,
                'message' => 'Product sample retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve product sample',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new product sample with images
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'images.*' => 'required_with:images|image|mimes:jpeg,png,gif,webp|max:5120', // 5MB max
            ]);

            // Create the product sample
            $productSample = ProductSample::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            // Handle image uploads
            if ($request->hasFile('images')) {
                $sortOrder = 0;
                foreach ($request->file('images') as $imageFile) {
                    // Store the image in public storage under product_samples/{id}/
                    $path = $imageFile->store("product_samples/{$productSample->id}", 'public');
                    
                    if (!$path) {
                        throw new \Exception('Failed to store image file');
                    }

                    // Create image record
                    ProductSampleImage::create([
                        'product_sample_id' => $productSample->id,
                        'image_path' => $path,
                        'sort_order' => $sortOrder,
                    ]);
                    
                    $sortOrder++;
                }
            }

            // Reload with images
            $productSample->load('images');

            return response()->json([
                'success' => true,
                'data' => $productSample,
                'message' => 'Product sample created successfully'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Validation failed'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create product sample',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing product sample and its images
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\ProductSample $productSample
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, ProductSample $productSample)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'images.*' => 'image|mimes:jpeg,png,gif,webp|max:5120', // 5MB max
            ]);

            // Update basic fields
            $productSample->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            // Handle new image uploads
            if ($request->hasFile('images')) {
                $sortOrder = $productSample->images()->max('sort_order') + 1 ?? 0;
                
                foreach ($request->file('images') as $imageFile) {
                    // Store the image
                    $path = $imageFile->store("product_samples/{$productSample->id}", 'public');
                    
                    if (!$path) {
                        throw new \Exception('Failed to store image file');
                    }

                    // Create image record
                    ProductSampleImage::create([
                        'product_sample_id' => $productSample->id,
                        'image_path' => $path,
                        'sort_order' => $sortOrder,
                    ]);
                    
                    $sortOrder++;
                }
            }

            // Reload with images
            $productSample->load('images');

            return response()->json([
                'success' => true,
                'data' => $productSample,
                'message' => 'Product sample updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Validation failed'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update product sample',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a product sample and all its images
     * 
     * @param \App\Models\ProductSample $productSample
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ProductSample $productSample)
    {
        try {
            // Delete all images from storage
            $images = $productSample->images()->get();
            foreach ($images as $image) {
                if (Storage::disk('public')->exists($image->image_path)) {
                    Storage::disk('public')->delete($image->image_path);
                }
            }

            // Delete the entire sample directory
            if (Storage::disk('public')->exists("product_samples/{$productSample->id}")) {
                Storage::disk('public')->deleteDirectory("product_samples/{$productSample->id}");
            }

            // Delete the database records (images cascade delete)
            $productSample->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product sample deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete product sample',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

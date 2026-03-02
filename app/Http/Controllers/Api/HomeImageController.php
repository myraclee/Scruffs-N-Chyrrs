<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomeImageController extends Controller
{
    /**
     * Get all home page images ordered by sort order
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $images = HomeImage::orderBy('sort_order')->get();
            
            return response()->json([
                'success' => true,
                'data' => $images,
                'message' => 'Home images retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve home images',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new home page image
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'image' => 'required|image|mimes:jpeg,png,gif,webp|max:5120', // 5MB max
            ]);

            // Store the image in public storage
            $path = $request->file('image')->store('home_images', 'public');

            if (!$path) {
                throw new \Exception('Failed to store image file');
            }

            // Get the next sort order
            $nextSortOrder = HomeImage::max('sort_order') + 1 ?? 0;

            // Create database record
            $homeImage = HomeImage::create([
                'image_path' => $path,
                'sort_order' => $nextSortOrder,
            ]);

            return response()->json([
                'success' => true,
                'data' => $homeImage,
                'message' => 'Home image uploaded successfully'
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
                'error' => 'Failed to upload home image',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a home page image
     * 
     * @param \App\Models\HomeImage $homeImage
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(HomeImage $homeImage)
    {
        try {
            // Delete the file from storage
            if (Storage::disk('public')->exists($homeImage->image_path)) {
                Storage::disk('public')->delete($homeImage->image_path);
            }

            // Delete the database record
            $homeImage->delete();

            return response()->json([
                'success' => true,
                'message' => 'Home image deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete home image',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

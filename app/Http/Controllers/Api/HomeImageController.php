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
     * Sync home page images in one save operation.
     *
     * Keeps submitted existing IDs, removes omitted ones, and appends new uploads.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(Request $request)
    {
        try {
            $validated = $request->validate([
                'existing_image_ids' => 'nullable|array',
                'existing_image_ids.*' => 'integer',
                'images' => 'nullable|array',
                'images.*' => 'required|image|mimes:jpeg,png,gif,webp|max:5120',
            ]);

            $existingImages = HomeImage::orderBy('sort_order')->get();
            $existingImageIdSet = $existingImages->pluck('id')->all();

            $submittedIds = $validated['existing_image_ids'] ?? [];
            if (!is_array($submittedIds)) {
                $submittedIds = [$submittedIds];
            }

            $orderedKeptIds = [];
            foreach ($submittedIds as $id) {
                $normalizedId = (int) $id;
                if ($normalizedId > 0 && in_array($normalizedId, $existingImageIdSet, true)) {
                    $orderedKeptIds[] = $normalizedId;
                }
            }
            $orderedKeptIds = array_values(array_unique($orderedKeptIds));

            $newUploadCount = 0;
            if ($request->hasFile('images')) {
                $newUploadCount = count($request->file('images'));
            }

            if ((count($orderedKeptIds) + $newUploadCount) < 1) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'images' => ['At least one home page image is required.'],
                    ],
                    'message' => 'Validation failed',
                ], 422);
            }

            foreach ($existingImages as $existingImage) {
                if (!in_array($existingImage->id, $orderedKeptIds, true)) {
                    if (Storage::disk('public')->exists($existingImage->image_path)) {
                        Storage::disk('public')->delete($existingImage->image_path);
                    }
                    HomeImage::whereKey($existingImage->id)->delete();
                }
            }

            $uploadedIds = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $path = $imageFile->store('home_images', 'public');

                    if (!$path) {
                        throw new \Exception('Failed to store image file');
                    }

                    $created = HomeImage::create([
                        'image_path' => $path,
                        'sort_order' => 0,
                    ]);

                    $uploadedIds[] = $created->id;
                }
            }

            $finalOrderedIds = array_merge($orderedKeptIds, $uploadedIds);

            foreach ($finalOrderedIds as $sortOrder => $imageId) {
                HomeImage::whereKey($imageId)->update(['sort_order' => $sortOrder]);
            }

            $finalImages = HomeImage::orderBy('sort_order')->get();

            return response()->json([
                'success' => true,
                'data' => $finalImages,
                'message' => 'Home images synced successfully',
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
                'error' => 'Failed to sync home images',
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MaterialController extends Controller
{
    /**
     * Get all materials with their associated products.
     * GET /api/materials
     */
    public function index(): JsonResponse
    {
        try {
            $materials = Material::with('products')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $materials,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch materials',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new material.
     * POST /api/materials
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:materials,name',
                'units' => 'required|integer|min:0',
                'description' => 'nullable|string',
                'products' => 'nullable|array',
                'products.*.id' => 'integer|exists:products,id',
                'products.*.quantity' => 'integer|min:1',
            ]);

            $material = Material::create([
                'name' => $validated['name'],
                'units' => $validated['units'],
                'description' => $validated['description'] ?? null,
            ]);

            // Attach products with consumption quantities
            if (!empty($validated['products'])) {
                $syncData = [];
                foreach ($validated['products'] as $product) {
                    $syncData[$product['id']] = ['quantity' => $product['quantity']];
                }
                $material->products()->sync($syncData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Material created successfully',
                'data' => $material->load('products'),
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
                'message' => 'Failed to create material',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single material with its products.
     * GET /api/materials/{id}
     */
    public function show(Material $material): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $material->load('products'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch material',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a material.
     * PUT /api/materials/{id}
     */
    public function update(Request $request, Material $material): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:materials,name,' . $material->id,
                'units' => 'required|integer|min:0',
                'description' => 'nullable|string',
                'products' => 'nullable|array',
                'products.*.id' => 'integer|exists:products,id',
                'products.*.quantity' => 'integer|min:1',
            ]);

            $material->update([
                'name' => $validated['name'],
                'units' => $validated['units'],
                'description' => $validated['description'] ?? null,
            ]);

            // Update product associations
            if (isset($validated['products'])) {
                $syncData = [];
                foreach ($validated['products'] as $product) {
                    $syncData[$product['id']] = ['quantity' => $product['quantity']];
                }
                $material->products()->sync($syncData);
            } else {
                $material->products()->detach();
            }

            return response()->json([
                'success' => true,
                'message' => 'Material updated successfully',
                'data' => $material->load('products'),
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
                'message' => 'Failed to update material',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a material.
     * DELETE /api/materials/{id}
     */
    public function destroy(Material $material): JsonResponse
    {
        try {
            // Detach all associated products
            $material->products()->detach();

            $material->delete();

            return response()->json([
                'success' => true,
                'message' => 'Material deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete material',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

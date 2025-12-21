<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEggCategoryRequest;
use App\Http\Requests\UpdateEggCategoryRequest;
use App\Models\EggCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EggCategoryController extends Controller
{
    /**
     * Display a listing of egg categories.
     */
    public function index(Request $request): JsonResponse
    {
        $query = EggCategory::query();

        if (!$request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        $categories = $query->orderBy('name')->get();

        return response()->json(['data' => $categories]);
    }

    /**
     * Store a newly created egg category.
     */
    public function store(StoreEggCategoryRequest $request): JsonResponse
    {
        $category = EggCategory::create($request->validated());

        return response()->json([
            'message' => 'Egg category created successfully.',
            'data' => $category,
        ], 201);
    }

    /**
     * Display the specified egg category.
     */
    public function show(EggCategory $category): JsonResponse
    {
        return response()->json(['data' => $category]);
    }

    /**
     * Update the specified egg category.
     */
    public function update(UpdateEggCategoryRequest $request, EggCategory $category): JsonResponse
    {
        $category->update($request->validated());

        return response()->json([
            'message' => 'Egg category updated successfully.',
            'data' => $category->fresh(),
        ]);
    }

    /**
     * Toggle category active status.
     */
    public function toggleActive(EggCategory $category): JsonResponse
    {
        $category->update(['is_active' => !$category->is_active]);

        return response()->json([
            'message' => 'Category status updated.',
            'data' => $category->fresh(),
        ]);
    }
}

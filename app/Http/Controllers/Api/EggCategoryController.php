<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEggCategoryRequest;
use App\Http\Requests\UpdateEggCategoryRequest;
use App\Http\Resources\EggCategoryResource;
use App\Models\EggCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EggCategoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(EggCategory::class, 'category');
    }

    /**
     * Display a listing of egg categories.
     */
    public function index(Request $request): JsonResponse
    {
        $query = EggCategory::query();

        if (!$request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        $query->withCount('batches')->orderBy('sort_order')->orderBy('name');

        // Support ?all=true for dropdowns, otherwise paginate
        if ($request->boolean('all')) {
            return response()->json(['data' => EggCategoryResource::collection($query->get())]);
        }

        return EggCategoryResource::collection($query->paginate($request->integer('per_page', 15)))->response();
    }

    /**
     * Store a newly created egg category.
     */
    public function store(StoreEggCategoryRequest $request): JsonResponse
    {
        $category = EggCategory::create($request->validated());

        return response()->json([
            'message' => 'Egg category created successfully.',
            'data' => new EggCategoryResource($category),
        ], 201);
    }

    /**
     * Display the specified egg category.
     */
    public function show(EggCategory $category): JsonResponse
    {
        $category->loadCount('batches');

        return response()->json(['data' => new EggCategoryResource($category)]);
    }

    /**
     * Update the specified egg category.
     */
    public function update(UpdateEggCategoryRequest $request, EggCategory $category): JsonResponse
    {
        $category->update($request->validated());

        return response()->json([
            'message' => 'Egg category updated successfully.',
            'data' => new EggCategoryResource($category->fresh()),
        ]);
    }

    /**
     * Toggle category active status.
     */
    public function toggleActive(EggCategory $category): JsonResponse
    {
        $this->authorize('update', $category);

        $category->update(['is_active' => !$category->is_active]);

        return response()->json([
            'message' => 'Category status updated.',
            'data' => new EggCategoryResource($category->fresh()),
        ]);
    }
}

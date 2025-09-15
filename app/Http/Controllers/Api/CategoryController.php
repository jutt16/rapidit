<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Service;
use App\Models\MaidPricing;

class CategoryController extends Controller
{
    // Get all categories with services
    public function index()
    {
        try {
            $categories = Category::with('services')->get();

            // Attach maid pricings to service ID = 5 (maid service)
            foreach ($categories as $category) {
                foreach ($category->services as $service) {
                    if ($service->id == 5) {
                        $service->maid_pricings = MaidPricing::all();
                    }
                }
            }

            return response()->json([
                'status' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching categories: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get single category with services
    public function show($id)
    {
        try {
            $category = Category::with('services')->findOrFail($id);

            // Attach maid pricings to service ID = 5 (maid service)
            foreach ($category->services as $service) {
                if ($service->id == 5) {
                    $service->maid_pricings = MaidPricing::all();
                }
            }

            return response()->json([
                'status' => true,
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found.'
            ], 404);
        }
    }
}

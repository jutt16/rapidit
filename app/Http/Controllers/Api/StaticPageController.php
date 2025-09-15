<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;

class StaticPageController extends Controller
{
    public function show($slug)
    {
        $page = StaticPage::where('slug', $slug)->first();

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'Page not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $page,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;

class BannerController extends Controller
{
    public function activeBanners()
    {
        $banners = Banner::where('status', 1)->get()->map(function ($banner) {
            return [
                'id' => $banner->id,
                'title' => $banner->title,
                'description' => $banner->description,
                'image' => asset('storage/' . $banner->image),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }
}

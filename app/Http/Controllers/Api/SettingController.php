<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function getSettings(Request $request)
    {
        $settings = Setting::where('key', 'initial_discount')->first();
        if ($settings) {
            return response()->json([
                'success' => true,
                'data' => $settings,
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Settings not found',
            ], 404);
        }
    }
}

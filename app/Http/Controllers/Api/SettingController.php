<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function getSettings(Request $request)
    {
        $initialDiscount = Setting::where('key', 'initial_discount')->first();
        $uptoInitialDiscount = Setting::where('key', 'upto_initial_discount')->first();
        
        if ($initialDiscount || $uptoInitialDiscount) {
            $data = [];
            
            if ($initialDiscount) {
                $data['initial_discount'] = $initialDiscount;
            }
            
            if ($uptoInitialDiscount) {
                $data['upto_initial_discount'] = $uptoInitialDiscount;
            }
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Settings not found',
            ], 404);
        }
    }
}

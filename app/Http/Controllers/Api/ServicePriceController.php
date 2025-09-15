<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CookPricing;
use App\Models\MaidPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServicePriceController extends Controller
{
    public function calculateCookPrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number_of_dishes' => 'required|integer|min:1',
            'number_of_people' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pricing = CookPricing::first();
        if (!$pricing) {
            return response()->json([
                'success' => false,
                'error' => 'Cook pricing not configured.'
            ], 500);
        }

        $totalPrice = CookPricing::calculateCost(
            $request->input('number_of_people'),
            $request->input('number_of_dishes')
        );

        return response()->json([
            'success' => true,
            'total_price' => round($totalPrice, 2)
        ], 200);
    }

    public function getMaidPrices()
    {
        $maidPrices = MaidPricing::orderBy('time')->get();
        return response()->json([
            'success' => true,
            'data' => $maidPrices
        ], 200);
    }
}

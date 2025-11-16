<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Zone;

class ZoneController extends Controller
{
    public function index()
    {
        $zones = Zone::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $zones,
        ]);
    }
}


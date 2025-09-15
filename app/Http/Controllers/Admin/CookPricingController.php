<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CookPricing;
use Illuminate\Http\Request;

class CookPricingController extends Controller
{
    public function index()
    {
        $pricing = CookPricing::first();
        return view('admin.cook_pricings.index', compact('pricing'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'base_price' => 'required|numeric|min:0',
            'additional_dish_charge' => 'required|numeric|min:0',
            'additional_person_percentage' => 'required|numeric|min:0|max:100',
        ]);

        CookPricing::updateOrCreate(
            ['id' => 1],
            $request->only(['base_price', 'additional_dish_charge', 'additional_person_percentage'])
        );

        return redirect()->route('admin.cook-pricings.index')->with('success', 'Cook pricing updated.');
    }
}

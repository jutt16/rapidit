<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaidPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaidPricingController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->get('sort', 'price_asc');

        $sortMapping = [
            'price_asc' => ['price', 'asc'],
            'price_desc' => ['price', 'desc'],
            'time_asc' => ['time', 'asc'],
            'time_desc' => ['time', 'desc'],
        ];

        [$column, $direction] = $sortMapping[$sort] ?? $sortMapping['price_asc'];

        $packages = MaidPricing::orderBy($column, $direction)->get();

        return view('admin.maid_pricings.index', compact('packages', 'sort'));
    }

    public function create()
    {
        return view('admin.maid_pricings.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'time' => 'required|integer|unique:maid_pricings,time',
            'price' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        MaidPricing::create($request->only('time', 'price', 'discount'));

        return redirect()->route('admin.maid-pricings.index')->with('success', 'Maid package added.');
    }

    public function edit(MaidPricing $maidPricing)
    {
        return view('admin.maid_pricings.edit', compact('maidPricing'));
    }

    public function update(Request $request, MaidPricing $maidPricing)
    {
        $validator = Validator::make($request->all(), [
            'time' => 'required|integer|unique:maid_pricings,time,' . $maidPricing->id,
            'price' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $maidPricing->update($request->only('time', 'price', 'discount'));

        return redirect()->route('admin.maid-pricings.index')->with('success', 'Maid package updated.');
    }

    public function destroy(MaidPricing $maidPricing)
    {
        $maidPricing->delete();

        return redirect()->route('admin.maid-pricings.index')->with('success', 'Maid package deleted.');
    }
}

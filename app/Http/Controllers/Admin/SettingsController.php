<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function edit()
    {
        $radius = Setting::get('provider_radius', 10);
        $initial_discount = Setting::get('initial_discount', 5); // default 5%
        
        return view('admin.settings.index', compact('radius', 'initial_discount'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'provider_radius' => 'required|numeric|min:0.1',
            'initial_discount' => 'required|numeric|min:0|max:100',
        ]);

        Setting::set('provider_radius', $request->provider_radius);
        Setting::set('initial_discount', $request->initial_discount);

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}

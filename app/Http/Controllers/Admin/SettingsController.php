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
        return view('admin.settings.provider_radius', compact('radius'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'provider_radius' => 'required|numeric|min:0.1', // in km
        ]);

        Setting::set('provider_radius', $request->provider_radius);

        return redirect()->back()->with('success', 'Provider radius updated.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        $radius = Setting::get('provider_radius', 10);
        $initial_discount = Setting::get('initial_discount', 5); // default 5%
        $app_timezone = Setting::get('app_timezone', config('app.timezone'));
        $timezones = collect(\DateTimeZone::listIdentifiers())
            ->map(fn ($tz) => ['value' => $tz, 'label' => $tz])
            ->prepend([
                'value' => 'Asia/Kolkata',
                'label' => 'India/Kolkata (Asia/Kolkata)',
            ])
            ->unique('value')
            ->values();
        $current_time = Carbon::now($app_timezone)->format('Y-m-d H:i:s');

        // dd($timezones);
        return view('admin.settings.index', compact('radius', 'initial_discount', 'app_timezone', 'timezones', 'current_time'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'provider_radius' => 'required|numeric|min:0.1',
            'initial_discount' => 'required|numeric|min:0|max:100',
            'app_timezone' => 'required|timezone',
        ]);

        Setting::set('provider_radius', $request->provider_radius);
        Setting::set('initial_discount', $request->initial_discount);
        Setting::set('app_timezone', $request->app_timezone);

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}

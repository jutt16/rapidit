<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartnerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PartnerPreferenceController extends Controller
{
    // ✅ Get logged-in partner’s preferences
    public function index()
    {
        $partner = PartnerProfile::where('user_id', Auth::id())->firstOrFail();

        $preferences = $partner->services()
            ->withPivot('own_tools_available')
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'own_tools_available' => (bool) $service->pivot->own_tools_available,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $preferences
        ]);
    }

    // ✅ Add a new preference
    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'own_tools_available' => 'boolean'
        ]);

        $partner = PartnerProfile::where('user_id', Auth::id())->firstOrFail();

        $partner->services()->syncWithoutDetaching([
            $request->service_id => [
                'own_tools_available' => $request->boolean('own_tools_available')
            ]
        ]);

        return response()->json(['status' => true, 'message' => 'Preference added successfully']);
    }

    // ✅ Update preference (toggle tools availability)
    public function update(Request $request, $serviceId)
    {
        $request->validate([
            'own_tools_available' => 'required|boolean'
        ]);

        $partner = PartnerProfile::where('user_id', Auth::id())->firstOrFail();

        $partner->services()->updateExistingPivot($serviceId, [
            'own_tools_available' => $request->boolean('own_tools_available')
        ]);

        return response()->json(['status' => true, 'message' => 'Preference updated successfully']);
    }

    // ✅ Remove a preference
    public function destroy($serviceId)
    {
        $partner = PartnerProfile::where('user_id', Auth::id())->firstOrFail();

        $partner->services()->detach($serviceId);

        return response()->json(['status' => true, 'message' => 'Preference removed successfully']);
    }
}

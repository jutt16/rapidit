<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ZoneController extends Controller
{
    public function index()
    {
        $zones = Zone::latest()->paginate(15);

        return view('admin.zones.index', compact('zones'));
    }

    public function create()
    {
        return view('admin.zones.create', [
            'zone' => new Zone(),
            'googleMapsKey' => config('services.google.maps_key'),
        ]);
    }

    public function store(Request $request)
    {
        Zone::create($this->validateZone($request));

        return redirect()
            ->route('admin.zones.index')
            ->with('success', 'Zone created successfully.');
    }

    public function edit(Zone $zone)
    {
        return view('admin.zones.edit', [
            'zone' => $zone,
            'googleMapsKey' => config('services.google.maps_key'),
        ]);
    }

    public function update(Request $request, Zone $zone)
    {
        $zone->update($this->validateZone($request, $zone->id));

        return redirect()
            ->route('admin.zones.index')
            ->with('success', 'Zone updated successfully.');
    }

    public function destroy(Zone $zone)
    {
        $zone->delete();

        return redirect()
            ->route('admin.zones.index')
            ->with('success', 'Zone deleted successfully.');
    }

    protected function validateZone(Request $request, ?int $zoneId = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('zones', 'name')->ignore($zoneId),
            ],
            'description' => ['nullable', 'string'],
            'coordinates' => ['required', 'string'],
            'color' => ['nullable', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'is_active' => ['required', 'boolean'],
        ]);

        $decoded = json_decode($validated['coordinates'], true);

        if (
            !is_array($decoded)
            || count($decoded) < 3
            || collect($decoded)->contains(function ($point) {
                return !is_array($point)
                    || !array_key_exists('lat', $point)
                    || !array_key_exists('lng', $point)
                    || !is_numeric($point['lat'])
                    || !is_numeric($point['lng']);
            })
        ) {
            throw ValidationException::withMessages([
                'coordinates' => 'A valid polygon with at least 3 latitude/longitude points is required.',
            ]);
        }

        $validated['coordinates'] = collect($decoded)
            ->map(fn ($point) => [
                'lat' => (float) $point['lat'],
                'lng' => (float) $point['lng'],
            ])
            ->values()
            ->toArray();

        $validated['color'] = $validated['color'] ?? '#FF7043';
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}


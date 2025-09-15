<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PartnerAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PartnerAvailabilityController extends Controller
{
    /**
     * Create or update partner availability (POST /api/partner/availability)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'partner') {
            return response()->json(['message' => 'Only partners can set availability.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'is_available'   => ['required', 'boolean'],
            'status'         => ['nullable', 'string', 'max:100'],
            'start_time'     => ['nullable', 'date_format:H:i', 'required_if:is_available,1'],
            'end_time'       => ['nullable', 'date_format:H:i', 'required_if:is_available,1', 'after:start_time'],
            'blocked_dates'  => ['nullable', 'array'],
            'blocked_dates.*' => ['date_format:Y-m-d'],
        ], [
            'blocked_dates.*.date_format' => 'Each blocked date must be in YYYY-MM-DD format.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // update or create by token user id
        $availability = PartnerAvailability::updateOrCreate(
            ['partner_id' => $user->id],
            [
                'is_available'  => $data['is_available'],
                'status'        => $data['status'] ?? null,
                'start_time'    => $data['start_time'] ?? null,
                'end_time'      => $data['end_time'] ?? null,
                'blocked_dates' => $data['blocked_dates'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Availability saved.',
            'data'    => $availability,
        ], 200);
    }

    /**
     * Get availability for a partner (GET /api/partner/me/availability)
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'partner') {
            return response()->json(['message' => 'Only partners have availability.'], 403);
        }

        $availability = $user->availability; // relation
        return response()->json([
            'success' => true,
            'data'    => $availability
        ], 200);
    }

    /**
     * Toggle partner availability (PATCH /api/partner/availability/toggle)
     */
    public function toggle(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'partner') {
            return response()->json(['message' => 'Only partners can toggle availability.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'is_available' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        $availability = PartnerAvailability::updateOrCreate(
            ['partner_id' => $user->id],
            ['is_available' => $data['is_available']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Availability toggled.',
            'data'    => $availability,
        ], 200);
    }
}

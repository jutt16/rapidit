<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\PartnerProfile;

class PartnerProfileController extends Controller
{
    public function index()
    {
        try {
            $profiles = PartnerProfile::with('services.category')->get();
            return response()->json([
                'status' => true,
                'data' => $profiles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching profiles: ' . $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Create partner profile.
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            // Validate input
            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:255',
                'gender' => 'nullable|string|in:male,female,other',
                'date_of_birth' => 'nullable|date',
                'profile_picture' => 'nullable|file|mimes:jpg,jpeg,png',
                'languages' => 'nullable|array',
                'languages.*' => 'string',
                'years_of_experience' => 'nullable|integer',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',

                'aadhar_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
                'pan_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
                'police_verification' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
                'covid_vaccination_certificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
                'selfie_with_costume' => 'nullable|file|mimes:jpg,jpeg,png',
                'intro_video' => 'nullable|file|mimes:mp4,mov,avi',
                'service_ids' => 'nullable|array', // services partner offers
                'service_ids.*' => 'integer|exists:services,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Save profile
            $data = $request->except([
                'profile_picture',
                'aadhar_card',
                'pan_card',
                'police_verification',
                'covid_vaccination_certificate',
                'selfie_with_costume',
                'intro_video',
                'service_ids',
            ]);

            $data['user_id'] = $user->id;

            // Handle file uploads
            foreach (
                [
                    'profile_picture',
                    'aadhar_card',
                    'pan_card',
                    'police_verification',
                    'covid_vaccination_certificate',
                    'selfie_with_costume',
                    'intro_video'
                ] as $field
            ) {
                if ($request->hasFile($field)) {
                    $data[$field] = $request->file($field)->store('partners/' . $user->id, 'public');
                }
            }

            $profile = PartnerProfile::create($data);

            // Attach services
            if ($request->has('service_ids')) {
                $profile->services()->sync($request->service_ids);
            }

            return response()->json([
                'status' => true,
                'message' => 'Partner profile created successfully.',
                'data' => $profile->load('services'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error creating profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get partner profile.
     */
    public function show(Request $request)
    {
        try {
            $user = $request->user();
            $profile = $user->partnerProfile()
                ->with(['user', 'services.category'])
                ->first();

            if (!$profile) {
                return response()->json([
                    'status' => false,
                    'message' => 'Partner profile not found.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $profile,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update partner profile.
     */
    public function update(Request $request)
    {
        try {
            $user = $request->user();
            $profile = $user->partnerProfile;

            if (!$profile) {
                return response()->json([
                    'status' => false,
                    'message' => 'Profile not found. Please create one first.',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'full_name' => 'sometimes|string|max:255',
                'gender' => 'nullable|string|in:male,female,other',
                'date_of_birth' => 'nullable|date',
                'languages' => 'nullable|array',
                'languages.*' => 'string',
                'years_of_experience' => 'nullable|integer',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'service_ids' => 'nullable|array',
                'service_ids.*' => 'integer|exists:services,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $request->except([
                'profile_picture',
                'aadhar_card',
                'pan_card',
                'police_verification',
                'covid_vaccination_certificate',
                'selfie_with_costume',
                'intro_video',
                'service_ids',
            ]);

            // Handle new file uploads (overwrite old)
            foreach (
                [
                    'profile_picture',
                    'aadhar_card',
                    'pan_card',
                    'police_verification',
                    'covid_vaccination_certificate',
                    'selfie_with_costume',
                    'intro_video'
                ] as $field
            ) {
                if ($request->hasFile($field)) {
                    if ($profile->$field) {
                        Storage::disk('public')->delete($profile->$field);
                    }
                    $data[$field] = $request->file($field)->store('partners/' . $user->id, 'public');
                }
            }

            $profile->update($data);

            // Sync services
            if ($request->has('service_ids')) {
                $profile->services()->sync($request->service_ids);
            }

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully.',
                'data' => $profile->load('services'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating profile: ' . $e->getMessage(),
            ], 500);
        }
    }
}

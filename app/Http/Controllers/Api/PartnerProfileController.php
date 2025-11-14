<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\PartnerProfile;
use App\Services\ZoneCoverageService;

class PartnerProfileController extends Controller
{
    protected ZoneCoverageService $zoneCoverage;

    public function __construct(ZoneCoverageService $zoneCoverage)
    {
        $this->zoneCoverage = $zoneCoverage;
    }

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

            // ✅ Check if profile already exists
            if ($user->partnerProfile()->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Profile already exists. Please update instead of creating a new one.',
                ], 409); // 409 Conflict
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:255',
                'gender' => 'nullable|string|in:male,female,other',
                'date_of_birth' => 'nullable|date',
                'profile_picture' => 'nullable|file|mimes:jpg,jpeg,png',
                'languages' => 'nullable|array',
                'languages.*' => 'string',
                'years_of_experience' => 'nullable|integer',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',

                'aadhar_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
                'pan_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
                'police_verification' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
                'covid_vaccination_certificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
                'selfie_with_costume' => 'nullable|file|mimes:jpg,jpeg,png',
                'intro_video' => 'nullable|file|mimetypes:video/mp4,video/mov,video/avi,video/mpeg|max:51200', // 50MB limit
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

            $this->zoneCoverage->assertWithinActiveZone(
                $request->input('latitude'),
                $request->input('longitude')
            );

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
            $profile = $user->partnerProfile()->firstOrFail();

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
                'latitude' => 'sometimes|required_with:longitude|numeric|between:-90,90',
                'longitude' => 'sometimes|required_with:latitude|numeric|between:-180,180',
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

            if ($request->hasAny(['latitude', 'longitude'])) {
                $latitude = $request->input('latitude', $profile->latitude);
                $longitude = $request->input('longitude', $profile->longitude);
                $this->zoneCoverage->assertWithinActiveZone($latitude, $longitude);
            }

            $profile->update($data);

            if($user->partner_status !== 'approved') {
                $user->partner_status = 'pending';
                $user->save();
            }

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

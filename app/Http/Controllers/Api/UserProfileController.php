<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    /**
     * Get the authenticated user's profile
     */
    public function get(Request $request)
    {
        $user = $request->user()->load('profile');
        
        // Check if user has already received initial discount
        $initialDiscountApplied = Booking::where('user_id', $user->id)
            ->where('initial_discount_applied', true)
            ->exists();

        return response()->json([
            'user' => $user,
            'initial_discount_applied' => $initialDiscountApplied,
        ]);
    }

    /**
     * Update or create the user's profile
     */
    public function update(Request $request)
    {
        try {
            $user = $request->user();

            // ✅ Validate inputs using Validator facade
            $validator = Validator::make($request->all(), [
                'name'          => 'sometimes|string|max:255',
                'phone'         => 'sometimes|string|max:20|unique:users,phone,' . $user->id,
                'email'         => 'sometimes|email|max:255',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // ✅ update user basic info (except email, which is in user_profiles)
            $user->update([
                'name'  => $data['name']  ?? $user->name,
                'phone' => $data['phone'] ?? $user->phone,
            ]);

            // ✅ check profile record (create if not exists)
            $profile = $user->profile ?: new UserProfile(['user_id' => $user->id]);

            // ✅ update email (in profile table)
            if (isset($data['email'])) {
                $profile->email = $data['email'];
            }

            // ✅ if profile image uploaded
            if ($request->hasFile('profile_image')) {
                if ($profile->profile_image && Storage::disk('public')->exists('profiles/' . $profile->profile_image)) {
                    Storage::disk('public')->delete('profiles/' . $profile->profile_image);
                }

                $filename = time() . '.' . $request->file('profile_image')->getClientOriginalExtension();
                $filename = time() . '.' . $request->file('profile_image')->getClientOriginalExtension();

                // store file and return its relative path
                $path = $request->file('profile_image')->storeAs('profiles', $filename, 'public');

                // store relative path in DB (e.g. "profiles/1695389321.jpg")
                $profile->profile_image = $path;
            }

            $profile->save();

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully',
                'user'    => $user->fresh('profile'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deleteProfile(Request $request)
    {
        $user = $request->user();
        $user->delete();
        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully.'
        ]);
    }
}

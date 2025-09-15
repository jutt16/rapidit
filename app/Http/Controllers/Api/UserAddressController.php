<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserAddressController extends Controller
{
    // Create Address
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'label' => 'required|string|max:100',
            'addressLine' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postalCode' => 'required|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $address = UserAddress::create($request->all());

        return response()->json(['success' => true, 'data' => $address], 201);
    }

    // Get all addresses for a user
    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $addresses = UserAddress::where('user_id', $request->user_id)->get();

        return response()->json(['success' => true, 'data' => $addresses]);
    }

    // Optional: Update address
    public function update(Request $request, UserAddress $address)
    {
        $validator = Validator::make($request->all(), [
            'label' => 'sometimes|string|max:100',
            'addressLine' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'postalCode' => 'sometimes|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $address->update($request->all());

        return response()->json(['success' => true, 'data' => $address]);
    }

    // Optional: Delete address
    public function destroy(UserAddress $address)
    {
        $address->delete();
        return response()->json(['success' => true, 'message' => 'Address deleted']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportMessage;

class SupportController extends Controller
{
    public function contact(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:191',
            'email'     => 'required|email',
            'message'   => 'required|string',
            'user_type' => 'required|in:user,partner,guest',
        ]);

        // Store user_id if user is authenticated
        if ($request->user()) {
            $validated['user_id'] = $request->user()->id;
        }

        $support = SupportMessage::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Your request has been submitted successfully!',
            'data'    => $support
        ], 201);
    }
}

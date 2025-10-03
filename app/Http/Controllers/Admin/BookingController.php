<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with([
            'user',
            'requests' => function ($q) {
                $q->where('status', 'accepted')->with('partner');
            }
        ])->get();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show($id)
    {
        $booking = Booking::with(['user', 'service', 'address', 'requests.partner'])->findOrFail($id);
        return view('admin.bookings.show', compact('booking'));
    }

    public function edit($id)
    {
        $booking = Booking::with(['user', 'service', 'requests.partner'])->findOrFail($id);
        return view('admin.bookings.edit', compact('booking'));
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $request->validate([
            'status' => 'required|string|in:pending,confirmed,completed,cancelled',
            'schedule_date' => 'required|date',
            'schedule_time' => 'required|string',
        ]);

        $booking->update($request->only(['status', 'schedule_date', 'schedule_time']));

        return redirect()->route('admin.bookings.index')->with('success', 'Booking updated successfully.');
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return redirect()->route('admin.bookings.index')->with('success', 'Booking deleted successfully.');
    }
}

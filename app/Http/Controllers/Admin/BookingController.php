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
            'address',
            'requests.partner.partnerProfile',
            'requests.partner.addresses',
        ])->latest()->get();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show($id)
    {
        $booking = Booking::with([
            'user',
            'service',
            'address',
            'requests.partner.partnerProfile',
        ])->findOrFail($id);
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

    // Export bookings to CSV
    public function export(Request $request)
    {
        $query = Booking::with([
            'user',
            'service',
            'address',
            'payment',
            'requests' => function ($q) {
                $q->where('status', 'accepted')->with('partner');
            }
        ]);

        // Apply filters if any
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        $bookings = $query->latest()->get();

        $fileName = 'bookings_export_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function() use ($bookings) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Booking ID',
                'Customer Name',
                'Customer Phone',
                'Service',
                'Partner Name',
                'Partner Phone',
                'Status',
                'Amount',
                'Payment Status',
                'Schedule Date',
                'Schedule Time',
                'Address',
                'City',
                'Pincode',
                'Created At',
                'Completed At'
            ]);

            // CSV rows
            foreach ($bookings as $booking) {
                $acceptedRequest = $booking->requests->first();
                $partner = $acceptedRequest ? $acceptedRequest->partner : null;

                fputcsv($file, [
                    $booking->id,
                    $booking->user->name ?? 'N/A',
                    $booking->user->phone ?? 'N/A',
                    $booking->service->name ?? 'N/A',
                    $partner ? $partner->name : 'Not Assigned',
                    $partner ? $partner->phone : 'N/A',
                    ucfirst($booking->status),
                    number_format($booking->amount, 2),
                    $booking->payment ? ucfirst($booking->payment->status) : 'N/A',
                    $booking->schedule_date,
                    $booking->schedule_time,
                    $booking->address ? $booking->address->address_line : 'N/A',
                    $booking->address ? $booking->address->city : 'N/A',
                    $booking->address ? $booking->address->pincode : 'N/A',
                    $booking->created_at,
                    $booking->completed_at ?? 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

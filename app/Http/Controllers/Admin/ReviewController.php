<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Show all reviews (pending + approved + rejected)
     */
    public function index()
    {
        $reviews = Review::with(['user', 'partner'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.reviews.index', compact('reviews'));
    }

    /**
     * Show a single review
     */
    public function show($id)
    {
        $review = Review::with(['user', 'partner', 'booking'])->findOrFail($id);

        return view('admin.reviews.show', compact('review'));
    }

    /**
     * Approve review
     */
    public function approve($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => 'approved']);

        return redirect()->route('admin.reviews.index')->with('success', 'Review approved successfully.');
    }

    /**
     * Reject review
     */
    public function reject($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => 'rejected']);

        return redirect()->route('admin.reviews.index')->with('success', 'Review rejected successfully.');
    }

    /**
     * Export reviews to CSV
     */
    public function export(Request $request)
    {
        $query = Review::with(['user', 'partner', 'booking']);

        // Apply filters if any
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        $reviews = $query->latest()->get();

        $fileName = 'reviews_export_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function() use ($reviews) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Review ID',
                'Reviewer Type',
                'Reviewer Name',
                'Reviewer Phone',
                'Partner Name',
                'Partner Phone',
                'Booking ID',
                'Rating',
                'Comment',
                'Status',
                'Created At'
            ]);

            // CSV rows
            foreach ($reviews as $review) {
                fputcsv($file, [
                    $review->id,
                    ucfirst($review->reviewer_type ?? 'customer'),
                    $review->user->name ?? 'N/A',
                    $review->user->phone ?? 'N/A',
                    $review->partner->name ?? 'N/A',
                    $review->partner->phone ?? 'N/A',
                    $review->booking_id,
                    $review->rating,
                    $review->comment ?? 'No comment',
                    ucfirst($review->status),
                    $review->created_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

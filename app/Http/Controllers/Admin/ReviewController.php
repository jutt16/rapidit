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
}

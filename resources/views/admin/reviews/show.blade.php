@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Review #{{ $review->id }}</h2>
        <a href="{{ route('admin.reviews.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <p><strong>Booking ID:</strong> #{{ $review->booking_id }}</p>
            <p><strong>Reviewer:</strong> {{ $review->user?->name ?? 'Partner #'.$review->partner_id }}</p>
            <p><strong>Target:</strong>
                @if($review->reviewer_type === 'user')
                Partner #{{ $review->partner_id }}
                @else
                User #{{ $review->user_id }}
                @endif
            </p>
            <p><strong>Rating:</strong>
                @for($i=1;$i<=5;$i++)
                    <i class="bi bi-star{{ $i <= $review->rating ? '-fill text-warning' : '' }}"></i>
                    @endfor
            </p>
            <p><strong>Comment:</strong></p>
            <div class="border p-3 rounded bg-light">{{ $review->comment ?? 'No comment' }}</div>
            <p class="mt-3"><strong>Status:</strong>
                <span class="badge bg-{{ $review->status === 'approved' ? 'success' : ($review->status === 'rejected' ? 'danger' : 'warning') }}">
                    {{ ucfirst($review->status) }}
                </span>
            </p>

            <div class="d-flex justify-content-end gap-2 mt-4">
                @if($review->status !== 'approved')
                <form action="{{ route('admin.reviews.approve', $review->id) }}" method="POST">
                    @csrf
                    <button class="btn btn-success btn-sm"><i class="bi bi-check-circle"></i> Approve</button>
                </form>
                @endif

                @if($review->status !== 'rejected')
                <form action="{{ route('admin.reviews.reject', $review->id) }}" method="POST">
                    @csrf
                    <button class="btn btn-danger btn-sm"><i class="bi bi-x-circle"></i> Reject</button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
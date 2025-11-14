@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">⭐ Reviews Management</h2>
        <a href="{{ route('admin.reviews.export', request()->query()) }}" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export CSV
        </a>
    </div>

    @include('admin.layouts.messages')

    <div class="card shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Booking</th>
                        <th>Reviewer</th>
                        <th>Target</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $review)
                    <tr>
                        <td>{{ $review->id }}</td>
                        <td>#{{ $review->booking_id }}</td>
                        <td>{{ $review->user?->name ?? 'Partner #'.$review->partner_id }}</td>
                        <td>
                            @if($review->reviewer_type === 'user')
                            Partner #{{ $review->partner_id }}
                            @else
                            User #{{ $review->user_id }}
                            @endif
                        </td>
                        <td>
                            @for($i=1;$i<=5;$i++)
                                <i class="bi bi-star{{ $i <= $review->rating ? '-fill text-warning' : '' }}"></i>
                                @endfor
                        </td>
                        <td>
                            <span class="badge bg-{{ $review->status === 'approved' ? 'success' : ($review->status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($review->status) }}
                            </span>
                        </td>
                        <td>{{ $review->created_at->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('admin.reviews.show', $review->id) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">No reviews found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $reviews->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
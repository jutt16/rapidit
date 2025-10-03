@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Booking Details</h1>

    {{-- Main Booking Info --}}
    <div class="row">
        {{-- User Info --}}
        <div class="col-md-6">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light">
                    <strong>User Information</strong>
                </div>
                <div class="card-body">
                    <p><i class="bi bi-person-fill"></i> <strong>Name:</strong> {{ $booking->user->name }}</p>
                    <p><i class="bi bi-hash"></i> <strong>User ID:</strong> {{ $booking->user->id }}</p>
                    <p><i class="bi bi-geo-alt-fill"></i> <strong>Address:</strong> {{ $booking->address->full_address ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Partner Info --}}
        <div class="col-md-6">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light">
                    <strong>Partner Information</strong>
                </div>
                <div class="card-body">
                    <p><i class="bi bi-person-badge"></i> 
                        <strong>Partner:</strong> {{ optional($booking->requests->first())->partner->name ?? 'Unassigned' }}
                    </p>
                    <p><i class="bi bi-clipboard-check"></i> 
                        <strong>Status:</strong> 
                        @if($booking->status == 'confirmed')
                            <span class="badge bg-primary">Confirmed</span>
                        @elseif($booking->status == 'completed')
                            <span class="badge bg-success">Completed</span>
                        @elseif($booking->status == 'cancelled')
                            <span class="badge bg-danger">Canceled</span>
                        @else
                            <span class="badge bg-secondary">{{ $booking->status ?? 'Pending' }}</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Booking Info --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light">
            <strong>Booking Information</strong>
        </div>
        <div class="card-body">
            <p><i class="bi bi-briefcase-fill"></i> <strong>Service:</strong> {{ $booking->service->name ?? '-' }}</p>
            <p><i class="bi bi-calendar-event"></i> <strong>Schedule:</strong> {{ $booking->schedule_date }} {{ $booking->schedule_time }}</p>
            <p><i class="bi bi-wallet2"></i> <strong>Payment Method:</strong> {{ ucfirst($booking->payment_method) ?? '-' }}</p>
        </div>
    </div>

    {{-- Payment Info --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light">
            <strong>Payment Details</strong>
        </div>
        <div class="card-body">
            <p><strong>Amount:</strong> ${{ number_format($booking->amount, 2) }}</p>
            <p><strong>Tax:</strong> ${{ number_format($booking->tax, 2) }}</p>
            <p><strong>Total:</strong> <span class="fw-bold">${{ number_format($booking->total_amount, 2) }}</span></p>
        </div>
    </div>

    {{-- Requests --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light">
            <strong>Booking Requests</strong>
        </div>
        <div class="card-body">
            @if($booking->requests->isEmpty())
                <p class="text-muted">No requests sent yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Partner</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($booking->requests as $request)
                                <tr>
                                    <td>{{ $request->partner->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge 
                                            {{ $request->status == 'accepted' ? 'bg-success' : 
                                               ($request->status == 'pending' ? 'bg-warning' : 'bg-danger') }}">
                                            {{ ucfirst($request->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Review --}}
    @if($booking->review)
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light">
            <strong>Review</strong>
        </div>
        <div class="card-body">
            <p><strong>Rating:</strong> ⭐ {{ $booking->review->rating }}/5</p>
            <p><strong>Comment:</strong> "{{ $booking->review->comment }}"</p>
        </div>
    </div>
    @endif

    {{-- Timestamps --}}
    <div class="text-muted mt-3">
        <small>
            <i class="bi bi-clock-history"></i> Created: {{ $booking->created_at->format('d M Y H:i') }} | 
            Updated: {{ $booking->updated_at->format('d M Y H:i') }}
        </small>
    </div>

    <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary mt-4">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>
@endsection

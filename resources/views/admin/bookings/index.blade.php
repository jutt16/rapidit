@extends('admin.layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Bookings</h1>
        <a href="{{ route('admin.bookings.export', request()->query()) }}" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export CSV
        </a>
    </div>

    @include('admin.layouts.messages')

                <table class="table table-bordered">
        <thead>
            <tr>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>User ID</th>
                            <th>User Location</th>
                            <th>Expert</th>
                            <th>Expert ID</th>
                            <th>Expert Location</th>
                <th>Status</th>
                <th>Scheduled At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $booking)
                        @php
                            $preferredStatuses = ['accepted', 'assigned', 'completed', 'finished', 'arrived', 'confirmed'];
                            $assignedRequest = $booking->requests->first(function ($request) use ($preferredStatuses) {
                                return in_array(strtolower($request->status ?? ''), $preferredStatuses);
                            }) ?? $booking->requests->first();
                            $expert = $assignedRequest?->partner;
                            $expertAddress = $expert?->addresses?->first();
                        @endphp
            <tr>
                            <td>#{{ $booking->id }}</td>
                            <td>
                                <strong>{{ $booking->user->name }}</strong>
                            </td>
                            <td>
                                <span class="text-monospace">#{{ $booking->user->id }}</span>
                            </td>
                            <td>
                                @if($booking->address)
                                    <span class="d-block">{{ $booking->address->city ?? 'City N/A' }}, {{ $booking->address->state ?? 'State N/A' }}</span>
                                    <small class="text-muted">{{ $booking->address->addressLine ?? '' }}</small>
                                @else
                                    <span class="text-muted">Not provided</span>
                                @endif
                            </td>
                            <td>
                                @if($expert)
                                    <strong>{{ $expert->name }}</strong>
                                @else
                                    <span class="text-muted">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                @if($expert)
                                    <span class="text-monospace">#{{ $expert->id }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($expertAddress)
                                    <span class="d-block">{{ $expertAddress->city ?? 'City N/A' }}, {{ $expertAddress->state ?? 'State N/A' }}</span>
                                    <small class="text-muted">{{ $expertAddress->addressLine ?? '' }}</small>
                                @elseif(!empty($expert?->partnerProfile?->latitude) && !empty($expert?->partnerProfile?->longitude))
                                    <span class="text-monospace small">{{ $expert->partnerProfile->latitude }}, {{ $expert->partnerProfile->longitude }}</span>
                                @elseif($expert)
                                    <span class="text-muted">Not captured</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                <td>
                    @php $status = strtolower($booking->status ?? ''); @endphp
                    @if($status === 'confirmed')
                    <span class="badge bg-primary">Confirmed</span>
                    @elseif($status === 'completed')
                    <span class="badge bg-success">Completed</span>
                    @elseif($status === 'cancelled')
                    <span class="badge bg-danger">Canceled</span>
                    @elseif($status === 'pending')
                    <span class="badge bg-warning text-dark">Pending</span>
                    @else
                    <span class="badge bg-secondary">{{ ucfirst($booking->status ?? 'Pending') }}</span>
                    @endif
                    @if($assignedRequest && $assignedRequest->status)
                        <div class="small text-muted">Request: {{ ucfirst($assignedRequest->status) }}</div>
                    @endif
                </td>
                <td>{{ $booking->schedule_date }}<br>{{ $booking->schedule_time }}</td>
                <td>
                    <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn btn-sm btn-info">View</a>
                    <a href="{{ route('admin.bookings.edit', $booking->id) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('admin.bookings.destroy', $booking->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
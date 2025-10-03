@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>Bookings</h1>

    @include('admin.layouts.messages')

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Partner</th>
                <th>Status</th>
                <th>Scheduled At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $booking)
            <tr>
                <td>{{ $booking->id }}</td>
                <td>{{ $booking->user->name }}</td>
                <td>{{ optional($booking->requests->first())->partner->name ?? 'Unassigned' }}</td>
                <td>
                    @if($booking->status == 'confirmed')
                    <span class="badge bg-primary">Confirmed</span>
                    @elseif($booking->status == 'completed')
                    <span class="badge bg-success">Completed</span>
                    @elseif($booking->status == 'cancelled')
                    <span class="badge bg-danger">Canceled</span>
                    @else
                    <span class="badge bg-secondary">{{ optional($booking->requests->first())->status ?? 'Pending' }}</span>
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
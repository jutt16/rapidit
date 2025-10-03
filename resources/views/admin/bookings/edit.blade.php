@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>Edit Booking</h1>

    <form action="{{ route('admin.bookings.update', $booking->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="pending" {{ $booking->status == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="confirmed" {{ $booking->status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="completed" {{ $booking->status == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ $booking->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Schedule Date</label>
            <input type="date" name="schedule_date" value="{{ $booking->schedule_date }}" class="form-control">
        </div>

        <div class="mb-3">
            <label>Schedule Time</label>
            <input type="text" name="schedule_time" value="{{ $booking->schedule_time }}" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection

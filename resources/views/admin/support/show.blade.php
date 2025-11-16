@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">📩 Support Message</h2>
        <a href="{{ route('admin.support.index') }}" class="btn btn-secondary btn-sm">← Back</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5><strong>Name:</strong> 
                @if($message->user_id && $message->user)
                    <a href="{{ route('admin.users.show', $message->user_id) }}" class="text-primary text-decoration-none">
                        {{ $message->name }}
                        <i class="fas fa-external-link-alt ms-1" style="font-size: 0.875rem;"></i>
                    </a>
                @else
                    {{ $message->name }}
                @endif
            </h5>
            <h6><strong>Email:</strong> {{ $message->email }}</h6>
            <h6><strong>User Type:</strong> {{ ucfirst($message->user_type) }}</h6>
            <hr>
            <p>{{ $message->message }}</p>
            <small class="text-muted">Submitted on {{ $message->created_at->format('d M Y, h:i A') }}</small>
        </div>
    </div>
</div>
@endsection
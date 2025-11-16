@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">📩 Support & Feedback</h2>
        <a href="{{ route('admin.support.export') }}" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export CSV
        </a>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>User Type</th>
                        <th>Message</th>
                        <th>Submitted At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($messages as $msg)
                    <tr>
                        <td>
                            @if($msg->user_id && $msg->user)
                                <a href="{{ route('admin.users.show', $msg->user_id) }}" class="text-primary text-decoration-none">
                                    {{ $msg->name }}
                                    <i class="fas fa-external-link-alt ms-1" style="font-size: 0.75rem;"></i>
                                </a>
                            @else
                                {{ $msg->name }}
                            @endif
                        </td>
                        <td>{{ $msg->email }}</td>
                        <td>{{ ucfirst($msg->user_type) }}</td>
                        <td>{{ Str::limit($msg->message, 50) }}</td>
                        <td>{{ $msg->created_at->format('d M Y, h:i A') }}</td>
                        <td>
                            <a href="{{ route('admin.support.show', $msg->id) }}" class="btn btn-primary btn-sm">
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-3">
                {{ $messages->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
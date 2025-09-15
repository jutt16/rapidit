@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <h2 class="fw-bold mb-4">📩 Support & Feedback</h2>
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
                        <td>{{ $msg->name }}</td>
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
@extends('admin.layouts.app')

@section('content')
@include('admin.layouts.messages')

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Notifications</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Notifications</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Notification List</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Notification</a>
                </div>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Topic</th>
                            <th>Sent</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifications as $index => $notification)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $notification->title }}</td>
                            <td>{{ $notification->topic }}</td>
                            <td>
                                @if($notification->sent)
                                <span class="badge bg-success">Yes</span>
                                @else
                                <span class="badge bg-danger">No</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.notifications.show', $notification->id) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('admin.notifications.edit', $notification->id) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.notifications.destroy', $notification->id) }}" method="POST" style="display:inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                </form>
                                <form action="{{ route('admin.notifications.resend', $notification->id) }}" method="POST" style="display:inline-block">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-redo"></i> Resend</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No notifications found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $notifications->links() }}
    </div>
</section>
@endsection
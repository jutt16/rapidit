@extends('admin.layouts.app')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">User Details</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Details</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@include('admin.layouts.messages')

<section class="content">
    <div class="container-fluid">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">{{ $user->name }}</h3>
            </div>
            <div class="card-body">
                <p><strong>Phone:</strong> {{ $user->phone }}</p>
                <p><strong>Role:</strong> {{ ucfirst($user->role) }}</p>
                <p><strong>Status:</strong>
                    @if($user->status)
                    <span class="badge bg-success">Active</span>
                    @else
                    <span class="badge bg-danger">Inactive</span>
                    @endif
                </p>
                <p><strong>Phone Verified:</strong>
                    @if($user->phone_verified)
                    <i class="fas fa-check text-success"></i>
                    @else
                    <i class="fas fa-times text-danger"></i>
                    @endif
                </p>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning">Edit</a>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </div>
</section>
@endsection
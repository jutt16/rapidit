@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>My Profile</h1>
        <a href="{{ route('admin.profile.edit') }}" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Profile
        </a>
    </div>

    @include('admin.layouts.messages')

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Profile Details</h5>
        </div>
        <div class="card-body row">
            <div class="col-md-4 text-center">
                @if($user->profile_picture)
                <img src="{{ asset('storage/'.$user->profile_picture) }}" class="img-fluid rounded mb-2" style="max-height:200px">
                @else
                <img src="{{ asset('admin-assets/dist/img/user2-160x160.jpg') }}" class="img-fluid rounded mb-2" alt="Profile Picture">
                @endif
            </div>

            <div class="col-md-8">
                <table class="table table-borderless table-sm">
                    <tr>
                        <th>Name:</th>
                        <td>{{ $user->name }}</td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td>{{ $user->phone }}</td>
                    </tr>
                    <tr>
                        <th>Role:</th>
                        <td>{{ ucfirst($user->role) }}</td>
                    </tr>
                    <tr>
                        <th>Phone Verified:</th>
                        <td>
                            @if($user->phone_verified)
                            <span class="badge bg-success">Yes</span>
                            @else
                            <span class="badge bg-danger">No</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Joined At:</th>
                        <td>{{ $user->created_at?->format('d-m-Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
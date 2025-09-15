@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Edit Profile</h1>
    </div>

    @include('admin.layouts.messages')

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Profile Details</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                @csrf

                <div class="col-md-4 text-center">
                    @if($user->profile_picture)
                        <img src="{{ asset('storage/'.$user->profile_picture) }}" class="img-fluid rounded mb-2" style="max-height:150px">
                    @else
                        <img src="{{ asset('admin-assets/dist/img/user2-160x160.jpg') }}" class="img-fluid rounded mb-2" alt="Profile Picture">
                    @endif
                    <input type="file" name="profile_picture" class="form-control form-control-sm mt-2">
                </div>

                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="{{ old('phone', $user->phone) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                        <input type="password" class="form-control" name="password">
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" name="password_confirmation">
                    </div>

                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

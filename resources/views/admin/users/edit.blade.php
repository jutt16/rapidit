@extends('admin.layouts.app')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Edit User</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Edit User</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@include('admin.layouts.messages')

<section class="content">
    <div class="container-fluid">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">Edit User Details</h3>
            </div>

            <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <!-- Name -->
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}" required>
                        @error('name')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone', $user->phone) }}" required>
                        @error('phone')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Role -->
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select name="role" class="form-control @error('role') is-invalid @enderror" required>
                            <option value="admin" {{ old('role', $user->role)=='admin' ? 'selected' : '' }}>Admin</option>
                            <option value="partner" {{ old('role', $user->role)=='partner' ? 'selected' : '' }}>Partner</option>
                            <option value="user" {{ old('role', $user->role)=='user' ? 'selected' : '' }}>User</option>
                        </select>
                        @error('role')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                            <option value="1" {{ old('status', $user->status)=='1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('status', $user->status)=='0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password <small>(leave blank if unchanged)</small></label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Enter new password">
                        @error('password')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Password Confirmation -->
                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control"
                               placeholder="Confirm new password">
                    </div>
                </div>

                <div class="card-footer">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-warning">Update User</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

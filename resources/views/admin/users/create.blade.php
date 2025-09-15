@extends('admin.layouts.app')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Add User</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Add User</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@include('admin.layouts.messages')

<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">User Details</h3>
            </div>

            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <!-- Name -->
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="Enter name" required>
                        @error('name')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone') }}" placeholder="Enter phone" required>
                        @error('phone')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Role -->
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select name="role" class="form-control @error('role') is-invalid @enderror" required>
                            <option value="">Select Role</option>
                            <option value="admin" {{ old('role')=='admin' ? 'selected' : '' }}>Admin</option>
                            <option value="partner" {{ old('role')=='partner' ? 'selected' : '' }}>Partner</option>
                            <option value="user" {{ old('role')=='user' ? 'selected' : '' }}>User</option>
                        </select>
                        @error('role')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                            <option value="1" {{ old('status')=='1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('status')=='0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Enter password" required>
                        @error('password')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Password Confirmation -->
                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control"
                               placeholder="Confirm password" required>
                    </div>
                </div>

                <div class="card-footer">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

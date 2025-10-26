@extends('admin.layouts.app')

@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Users</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Users</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@include('admin.layouts.messages')

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Filter + Search Form -->
        <div class="mb-3">
            <form action="{{ route('admin.users.index') }}" method="GET" class="form-inline">

                {{-- Role Filter --}}
                <div class="form-group mr-2">
                    <select name="role" class="form-control">
                        <option value="">-- All Roles --</option>
                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="partner" {{ request('role') == 'partner' ? 'selected' : '' }}>Partner</option>
                        <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>User</option>
                    </select>
                </div>

                {{-- Backend Search --}}
                <div class="form-group mr-2">
                    <input type="text" name="search" class="form-control"
                           value="{{ request('search') }}"
                           placeholder="Search by name or phone...">
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>

                @if(request('role') || request('search'))
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary ml-2">
                    <i class="fas fa-times"></i> Reset
                </a>
                @endif
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">User List</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add User
                    </a>
                </div>
            </div>

            <div class="card-body table-responsive p-0">
                <table id="usersTable" class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Verified</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $index => $user)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @if($user->role == 'partner')
                                    {{ $user->partnerProfile?->full_name ?? $user->name ?? 'N/A' }}
                                @else
                                    {{ $user->name ?? 'N/A' }}
                                @endif
                            </td>
                            <td>{{ $user->phone ?? 'N/A' }}</td>
                            <td>
                                <span class="badge 
                                    @if($user->role === 'admin') bg-danger
                                    @elseif($user->role === 'partner') bg-info
                                    @else bg-secondary @endif">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td>
                                @if($user->status)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @if($user->phone_verified)
                                    <i class="fas fa-check text-success"></i>
                                @else
                                    <i class="fas fa-times text-danger"></i>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No users found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer clearfix">
                {{-- Pagination (Laravel) --}}
                {{ $users->links() }}
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<!-- ✅ AdminLTE DataTables JS Integration -->
<script>
    $(function () {
        // Initialize DataTables on your table
        $('#usersTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,    // Enable client-side search
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "language": {
                "search": "Quick Filter:",
                "lengthMenu": "Show _MENU_ entries per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ users"
            }
        });
    });
</script>
@endsection

@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Partners</h1>

    @include('admin.layouts.messages')

    <div class="card shadow-sm">
        <div class="card-header">
            <div class="row align-items-center g-2">
                <div class="col-md-8 col-sm-12">
                    <h3 class="card-title mb-0">Partner List</h3>
                </div>

                <div class="col-md-4 col-sm-12">
                    <form method="GET" class="d-flex flex-wrap align-items-center justify-content-end" style="gap: 6px;">
                        <!-- Status Filter -->
                        <select name="status" class="form-control form-control-sm w-auto">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>

                        <!-- Search Input -->
                        <div class="input-group input-group-sm" style="max-width: 200px;">
                            <input type="text" name="search" class="form-control" placeholder="Search name or phone"
                                   value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Filter Button -->
                        <button class="btn btn-secondary btn-sm" type="submit">
                            <i class="fas fa-filter"></i>
                        </button>

                        <!-- Clear Button -->
                        @if(request('search') || request('status'))
                            <a href="{{ route('admin.partners.index') }}" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Verified</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($partners as $index => $partner)
                    <tr>
                        <td>{{ $partners->firstItem() + $index }}</td>
                        <td>{{ $partner->partnerProfile?->full_name ?? $partner->name ?? 'N/A' }}</td>
                        <td>{{ $partner->phone ?? 'N/A' }}</td>
                        <td>
                            <span class="badge 
                                @if($partner->partner_status == 'pending') bg-warning
                                @elseif($partner->partner_status == 'approved') bg-success
                                @else bg-danger @endif">
                                {{ ucfirst($partner->partner_status) }}
                            </span>
                        </td>
                        <td>
                            @if($partner->phone_verified)
                                <i class="fas fa-check text-success"></i>
                            @else
                                <i class="fas fa-times text-danger"></i>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.partners.show', $partner) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">No partners found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer clearfix">
            {{ $partners->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

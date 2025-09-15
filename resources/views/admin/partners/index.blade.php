@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Partners</h1>

    @include('admin.layouts.messages')

    <!-- Filter -->
    <form method="GET" class="mb-3 d-flex gap-2">
        <select name="status" class="form-control" style="width:200px">
            <option value="">All Statuses</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
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
                        <td>{{ $partner->name }}</td>
                        <td>{{ $partner->phone }}</td>
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
                            <a href="{{ route('admin.partners.show', $partner) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">No partners found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-2 px-3">
                {{ $partners->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
@extends('admin.layouts.app')

@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Search Results</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Search</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

@include('admin.layouts.messages')

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        
        <div class="mb-4">
            <h4>Search Results for: "<strong>{{ $query }}</strong>"</h4>
        </div>

        @if(empty($results))
            <div class="alert alert-info">
                No results found for your search query.
            </div>
        @else
            <!-- Users Results -->
            @if(isset($results['users']) && $results['users']->count() > 0)
                <div class="card mb-3">
                    <div class="card-header bg-info">
                        <h3 class="card-title"><i class="fas fa-users"></i> Users ({{ $results['users']->count() }})</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results['users'] as $user)
                                    <tr>
                                        <td>{{ $user->id }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->phone }}</td>
                                        <td><span class="badge badge-primary">{{ $user->role }}</span></td>
                                        <td><span class="badge badge-{{ $user->status == 'active' ? 'success' : 'danger' }}">{{ $user->status }}</span></td>
                                        <td>
                                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Partners Results -->
            @if(isset($results['partners']) && $results['partners']->count() > 0)
                <div class="card mb-3">
                    <div class="card-header bg-warning">
                        <h3 class="card-title"><i class="fas fa-user-tie"></i> Partners ({{ $results['partners']->count() }})</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Partner Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results['partners'] as $partner)
                                    <tr>
                                        <td>{{ $partner->id }}</td>
                                        <td>{{ $partner->name }}</td>
                                        <td>{{ $partner->phone }}</td>
                                        <td>
                                            <span class="badge badge-{{ $partner->partner_status == 'approved' ? 'success' : ($partner->partner_status == 'pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($partner->partner_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.partners.show', $partner) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Bookings Results -->
            @if(isset($results['bookings']) && $results['bookings']->count() > 0)
                <div class="card mb-3">
                    <div class="card-header bg-primary">
                        <h3 class="card-title"><i class="fas fa-calendar-check"></i> Bookings ({{ $results['bookings']->count() }})</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Service</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results['bookings'] as $booking)
                                    <tr>
                                        <td>{{ $booking->id }}</td>
                                        <td>{{ $booking->user->name ?? 'N/A' }}</td>
                                        <td>{{ $booking->service->name ?? 'N/A' }}</td>
                                        <td>₹{{ number_format($booking->total_amount, 2) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $booking->status == 'completed' ? 'success' : ($booking->status == 'accepted' ? 'info' : 'secondary') }}">
                                                {{ ucfirst($booking->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $booking->created_at->format('d M Y') }}</td>
                                        <td>
                                            <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Withdrawals Results -->
            @if(isset($results['withdrawals']) && $results['withdrawals']->count() > 0)
                <div class="card mb-3">
                    <div class="card-header bg-dark">
                        <h3 class="card-title"><i class="fas fa-university"></i> Withdrawals ({{ $results['withdrawals']->count() }})</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Fee</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results['withdrawals'] as $withdrawal)
                                    <tr>
                                        <td>{{ $withdrawal->id }}</td>
                                        <td>{{ $withdrawal->user->name ?? 'N/A' }}</td>
                                        <td>₹{{ number_format($withdrawal->amount, 2) }}</td>
                                        <td>₹{{ number_format($withdrawal->fee, 2) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $withdrawal->status == 'completed' ? 'success' : ($withdrawal->status == 'processing' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($withdrawal->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $withdrawal->created_at->format('d M Y') }}</td>
                                        <td>
                                            <a href="{{ route('admin.withdrawals.show', $withdrawal) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif

    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->
@endsection


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
        <div class="row">
            <div class="col-lg-4">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title mb-0">{{ $user->name }}</h3>
                    </div>
                    <div class="card-body">
                        <dl class="mb-0">
                            <dt>Customer ID</dt>
                            <dd>#{{ $user->id }}</dd>

                            <dt class="mt-3">Phone</dt>
                            <dd>{{ $user->phone ?? 'N/A' }}</dd>

                            <dt class="mt-3">Role</dt>
                            <dd>{{ ucfirst($user->role) }}</dd>

                            <dt class="mt-3">Status</dt>
                            <dd>
                                @if($user->status)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </dd>

                            <dt class="mt-3">Phone Verified</dt>
                            <dd>
                                @if($user->phone_verified)
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Yes</span>
                                @else
                                    <span class="badge bg-danger"><i class="fas fa-times me-1"></i>No</span>
                                @endif
                            </dd>

                            <dt class="mt-3">Registered</dt>
                            <dd>{{ $user->created_at?->format('d M Y H:i') ?? '—' }}</dd>

                            <dt class="mt-3">Last Updated</dt>
                            <dd>{{ $user->updated_at?->format('d M Y H:i') ?? '—' }}</dd>
                        </dl>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                @php
                    $profileImage = $user->profile?->profile_image
                        ? asset('storage/' . $user->profile->profile_image)
                        : asset('admin-assets/dist/img/user2-160x160.jpg');
                @endphp

                <div class="card card-outline card-primary mb-3">
                    <div class="card-header d-flex align-items-center">
                        <img src="{{ $profileImage }}" alt="Profile" class="rounded-circle me-3" width="48" height="48">
                        <div>
                            <h3 class="card-title mb-0">Profile Details</h3>
                            <small class="text-muted">Primary profile information synced from the app</small>
                        </div>
                    </div>
                    <div class="card-body row">
                        <div class="col-md-6">
                            <p><strong>Email:</strong> {{ $user->profile?->email ?? 'Not provided' }}</p>
                            <p><strong>Display Name:</strong> {{ $user->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>FCM Token:</strong> <span class="text-monospace small">{{ $user->fcm_token ?? '—' }}</span></p>
                        </div>
                    </div>
                </div>

                <div class="card card-outline card-secondary mb-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Address Details</h3>
                    </div>
                    <div class="card-body p-0">
                        @if($user->addresses->isEmpty())
                            <p class="p-3 text-muted mb-0">No addresses have been added for this user.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Label</th>
                                            <th>Full Address</th>
                                            <th>City</th>
                                            <th>State</th>
                                            <th>Coordinates</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($user->addresses as $address)
                                            <tr>
                                                <td>{{ $address->label ?? '—' }}</td>
                                                <td>{{ $address->addressLine ?? '—' }}</td>
                                                <td>{{ $address->city ?? '—' }}</td>
                                                <td>{{ $address->state ?? '—' }}</td>
                                                <td>
                                                    @if($address->latitude && $address->longitude)
                                                        <span class="text-monospace small">
                                                            {{ $address->latitude }}, {{ $address->longitude }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                @if($user->role === 'partner')
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Partner Profile</h3>
                        </div>
                        <div class="card-body">
                            @if($user->partnerProfile)
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Expert ID:</strong> #{{ $user->id }}</p>
                                        <p><strong>Full Name:</strong> {{ $user->partnerProfile->full_name ?? '—' }}</p>
                                        <p><strong>Gender:</strong> {{ ucfirst($user->partnerProfile->gender ?? '—') }}</p>
                                        <p><strong>Date of Birth:</strong> {{ $user->partnerProfile->date_of_birth?->format('d M Y') ?? '—' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Experience:</strong> {{ $user->partnerProfile->years_of_experience ?? '0' }} yrs</p>
                                        <p><strong>Languages:</strong> {{ $user->partnerProfile->languages ? implode(', ', $user->partnerProfile->languages) : '—' }}</p>
                                        <p><strong>Location:</strong>
                                            @if($user->partnerProfile->latitude && $user->partnerProfile->longitude)
                                                <span class="text-monospace small">
                                                    {{ $user->partnerProfile->latitude }}, {{ $user->partnerProfile->longitude }}
                                                </span>
                                            @else
                                                <span class="text-muted">Not captured</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @else
                                <p class="text-muted mb-0">Partner profile details are not available.</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
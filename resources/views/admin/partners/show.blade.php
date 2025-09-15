@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Partner Details</h1>
        <a href="{{ route('admin.partners.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    @include('admin.layouts.messages')

    <!-- Partner Info Card -->
    <div class="card mb-3 shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Basic Information</h5>
        </div>
        <div class="card-body row">
            <div class="col-md-3 text-center">
                @if($user->partnerProfile && $user->partnerProfile->profile_picture)
                <img src="{{ asset('storage/'.$user->partnerProfile->profile_picture) }}" class="img-fluid rounded mb-2" alt="Profile Picture">
                @else
                <img src="{{ asset('admin-assets/dist/img/user2-160x160.jpg') }}" class="img-fluid rounded mb-2" alt="Profile Picture">
                @endif
            </div>
            <div class="col-md-9">
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
                        <th>Partner Status:</th>
                        <td>
                            <span class="badge 
                                @if($user->partner_status == 'pending') bg-warning
                                @elseif($user->partner_status == 'approved') bg-success
                                @else bg-danger @endif">
                                {{ ucfirst($user->partner_status) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Gender:</th>
                        <td>{{ $user->partnerProfile->gender ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>DOB:</th>
                        <td>{{ $user->partnerProfile->date_of_birth?->format('d-m-Y') ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Experience:</th>
                        <td>{{ $user->partnerProfile->years_of_experience ?? 'N/A' }} years</td>
                    </tr>
                    <tr>
                        <th>Languages:</th>
                        <td>{{ $user->partnerProfile->languages ? implode(', ', $user->partnerProfile->languages) : 'N/A' }}</td>
                    </tr>
                </table>

                <!-- Update Status Card -->
                <div class="card mt-3 shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Update Partner Status</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.partners.updateStatus', $user) }}" method="POST" class="row g-3 align-items-center">
                            @csrf
                            <div class="col-md-3 col-sm-12">
                                <label for="partner_status" class="form-label mb-0">Change Status:</label>
                            </div>
                            <div class="col-md-4 col-sm-12">
                                <select name="partner_status" id="partner_status" class="form-select form-select-sm">
                                    <option value="pending" {{ $user->partner_status == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ $user->partner_status == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ $user->partner_status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-12">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Update Status</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Services Offered Card -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Services Offered</h5>
        </div>
        <div class="card-body">
            @if($user->partnerProfile && $user->partnerProfile->services->count())
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Service Name</th>
                        <th>Category</th>
                        <th>Own Tools Available</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($user->partnerProfile->services as $index => $service)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $service->name }}</td>
                        <td>{{ $service->category->name ?? 'N/A' }}</td>
                        <td>
                            @if($service->pivot->own_tools_available)
                            <span class="badge bg-success">Yes</span>
                            @else
                            <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="text-center text-muted">No services assigned to this partner.</p>
            @endif
        </div>
    </div>

    <!-- Documents Card -->
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Uploaded Documents</h5>
        </div>
        <div class="card-body row">
            @php
            $docs = [
            'Aadhar Card' => $user->partnerProfile->aadhar_card ?? null,
            'PAN Card' => $user->partnerProfile->pan_card ?? null,
            'Police Verification' => $user->partnerProfile->police_verification ?? null,
            'Covid Certificate' => $user->partnerProfile->covid_vaccination_certificate ?? null,
            'Selfie with Costume' => $user->partnerProfile->selfie_with_costume ?? null,
            'Intro Video' => $user->partnerProfile->intro_video ?? null,
            ];
            @endphp

            @foreach($docs as $label => $file)
            <div class="col-md-4 mb-3">
                <p><strong>{{ $label }}</strong></p>
                @if($file)
                @php
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                @endphp

                @if(in_array($ext, ['jpg','jpeg','png','gif']))
                <img src="{{ asset('storage/'.$file) }}" class="img-fluid border rounded" style="max-height:150px" alt="{{ $label }}">
                @elseif(in_array($ext, ['mp4','mov','avi']))
                <video controls style="width:100%; max-height:150px">
                    <source src="{{ asset('storage/'.$file) }}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                @else
                <a href="{{ asset('storage/'.$file) }}" target="_blank" class="btn btn-outline-primary btn-sm">Download {{ $label }}</a>
                @endif
                @else
                <span class="text-muted">Not uploaded</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
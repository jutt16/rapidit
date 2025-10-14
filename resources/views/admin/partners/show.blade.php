@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="m-0 text-dark"><i class="fas fa-user-tie me-2"></i> Partner Details</h1>
        <a href="{{ route('admin.partners.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>

    @include('admin.layouts.messages')

    <!-- Partner Info Card -->
    <div class="card card-primary card-outline shadow-sm mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-info-circle me-2"></i> Basic Information</h3>
        </div>
        <div class="card-body row">

            <div class="col-md-3 text-center border-end">
                <img src="{{ $user->partnerProfile && $user->partnerProfile->profile_picture 
                    ? asset('storage/'.$user->partnerProfile->profile_picture) 
                    : asset('admin-assets/dist/img/user2-160x160.jpg') }}"
                    class="img-fluid rounded-circle shadow-sm mb-3"
                    style="max-width: 150px;"
                    alt="Profile Picture">

                <h5 class="fw-bold mb-0">{{ $user->name }}</h5>
                <span class="text-muted small">{{ ucfirst($user->partner_status) }}</span>
            </div>

            <div class="col-md-9">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <tbody>
                            <tr><th style="width: 30%">Phone:</th><td>{{ $user->phone }}</td></tr>
                            <tr>
                                <th>Phone Verified:</th>
                                <td>{!! $user->phone_verified ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>' !!}</td>
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
                            <tr><th>Gender:</th><td>{{ $user->partnerProfile->gender ?? 'N/A' }}</td></tr>
                            <tr><th>Date of Birth:</th><td>{{ $user->partnerProfile->date_of_birth?->format('d-m-Y') ?? 'N/A' }}</td></tr>
                            <tr><th>Experience:</th><td>{{ $user->partnerProfile->years_of_experience ?? 'N/A' }} years</td></tr>
                            <tr><th>Languages:</th><td>{{ $user->partnerProfile->languages ? implode(', ', $user->partnerProfile->languages) : 'N/A' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Card -->
    <div class="card card-warning card-outline shadow-sm mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-edit me-2"></i> Update Partner Status</h3>
        </div>
        <form action="{{ route('admin.partners.updateStatus', $user) }}" method="POST" class="card-body">
            @csrf
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="partner_status">Change Status</label>
                        <select name="partner_status" id="partner_status" class="form-control select2">
                            <option value="pending" {{ $user->partner_status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ $user->partner_status == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ $user->partner_status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-8" id="rejection_notes_box" style="display: {{ $user->partner_status == 'rejected' ? 'block' : 'none' }};">
                    <div class="form-group mb-3">
                        <label for="rejection_notes">Rejection Notes</label>
                        <textarea name="rejection_notes" id="rejection_notes" rows="3" class="form-control"
                            placeholder="Enter reason for rejection">{{ old('rejection_notes', $user->rejection_notes) }}</textarea>
                    </div>
                </div>

                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Status</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Services Offered -->
    <div class="card card-info card-outline shadow-sm mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-briefcase me-2"></i> Services Offered</h3>
        </div>
        <div class="card-body p-0">
            @if($user->partnerProfile && $user->partnerProfile->services->count())
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th style="width:5%">#</th>
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
                            <span class="badge {{ $service->pivot->own_tools_available ? 'bg-success' : 'bg-secondary' }}">
                                {{ $service->pivot->own_tools_available ? 'Yes' : 'No' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="p-3 text-center text-muted">No services assigned to this partner.</div>
            @endif
        </div>
    </div>

    <!-- Documents -->
    <div class="card card-secondary card-outline shadow-sm mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-alt me-2"></i> Uploaded Documents</h3>
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
            <div class="col-md-4 mb-4 text-center">
                <p class="fw-bold mb-1">{{ $label }}</p>
                @if($file)
                    @php $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)); @endphp
                    @if(in_array($ext, ['jpg','jpeg','png','gif']))
                        <img src="{{ asset('storage/'.$file) }}" class="img-fluid rounded border shadow-sm" style="max-height:180px;">
                    @elseif(in_array($ext, ['mp4','mov','avi']))
                        <video controls class="rounded border shadow-sm" style="width:100%; max-height:180px;">
                            <source src="{{ asset('storage/'.$file) }}" type="video/mp4">
                        </video>
                    @else
                        <a href="{{ asset('storage/'.$file) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download me-1"></i> Download {{ $label }}
                        </a>
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

@section('customJS')
<script>
$(document).ready(function () {
    // Works for normal select
    $('#partner_status').on('change', function() {
        toggleRejectionNotes($(this).val());
    });

    // Works for Select2
    $('#partner_status').on('select2:select', function (e) {
        toggleRejectionNotes(e.params.data.id);
    });

    function toggleRejectionNotes(value) {
        if (value === 'rejected') {
            $('#rejection_notes_box').slideDown();
        } else {
            $('#rejection_notes_box').slideUp();
        }
    }
});
</script>
@endsection
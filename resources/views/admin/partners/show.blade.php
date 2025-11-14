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
    @php
        $partnerProfile = $user->partnerProfile;
    @endphp

    @isset($partnerStats)
    <div class="card card-light border shadow-sm mb-4">
        <div class="card-header bg-white">
            <h3 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i> Lifetime Partner Performance</h3>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-lg-3">
                    <div class="info-box bg-white border h-100 shadow-sm">
                        <span class="info-box-icon text-primary"><i class="fas fa-mail-bulk"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text text-muted text-uppercase small">Total Requests</span>
                            <span class="info-box-number h3 mb-0">{{ number_format($partnerStats['total_requests'] ?? 0) }}</span>
                            <span class="text-muted small">All time</span>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $statusCards = [
                    ['label' => 'Accepted', 'value' => $partnerStats['accepted_requests'] ?? 0, 'icon' => 'check-circle', 'color' => 'text-success', 'sub' => 'Requests'],
                    ['label' => 'Rejected', 'value' => $partnerStats['rejected_requests'] ?? 0, 'icon' => 'times-circle', 'color' => 'text-danger', 'sub' => 'Requests'],
                    ['label' => 'Completed', 'value' => $partnerStats['completed_jobs'] ?? 0, 'icon' => 'flag-checkered', 'color' => 'text-info', 'sub' => 'Jobs'],
                    ['label' => 'Cancelled', 'value' => $partnerStats['cancelled_jobs'] ?? 0, 'icon' => 'ban', 'color' => 'text-warning', 'sub' => 'Jobs'],
                ];
                $earnings = $partnerStats['earnings'] ?? ['lifetime' => 0, 'mtd' => 0, 'ytd' => 0];
            @endphp

            <div class="row g-3">
                @foreach($statusCards as $card)
                <div class="col-sm-6 col-lg-3">
                    <div class="info-box bg-white border h-100 shadow-sm">
                        <span class="info-box-icon {{ $card['color'] }}"><i class="fas fa-{{ $card['icon'] }}"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text text-muted text-uppercase small">{{ $card['label'] }}</span>
                            <span class="info-box-number h4 mb-0">{{ number_format($card['value']) }}</span>
                            <span class="text-muted small">{{ $card['sub'] }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <div class="info-box bg-white border h-100 shadow-sm">
                        <span class="info-box-icon text-success"><i class="fas fa-rupee-sign"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text text-muted text-uppercase small">Lifetime Earnings</span>
                            <span class="info-box-number h4 mb-0">&#8377;{{ number_format($earnings['lifetime'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box bg-white border h-100 shadow-sm">
                        <span class="info-box-icon text-primary"><i class="fas fa-calendar-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text text-muted text-uppercase small">MTD Earnings</span>
                            <span class="info-box-number h4 mb-0">&#8377;{{ number_format($earnings['mtd'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box bg-white border h-100 shadow-sm">
                        <span class="info-box-icon text-dark"><i class="fas fa-calendar"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text text-muted text-uppercase small">YTD Earnings</span>
                            <span class="info-box-number h4 mb-0">&#8377;{{ number_format($earnings['ytd'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endisset

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
                            <tr><th style="width: 30%">Expert ID:</th><td>#{{ $user->id }}</td></tr>
                            <tr><th>Phone:</th><td>{{ $user->phone }}</td></tr>
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
                            <tr><th>Gender:</th><td>{{ optional($partnerProfile)->gender ?? 'N/A' }}</td></tr>
                            <tr><th>Date of Birth:</th><td>{{ optional($user->partnerProfile?->date_of_birth)->format('d-m-Y') ?? 'N/A' }}</td></tr>
                            <tr>
                                <th>Experience:</th>
                                <td>
                                    @if(!is_null(optional($partnerProfile)->years_of_experience))
                                        {{ optional($partnerProfile)->years_of_experience }} years
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            @php
                                $languages = optional($partnerProfile)->languages;
                                if (is_string($languages)) {
                                    $decoded = json_decode($languages, true);
                                    $languages = json_last_error() === JSON_ERROR_NONE ? $decoded : explode(',', $languages);
                                }
                            @endphp
                            <tr>
                                <th>Languages:</th>
                                <td>
                                    @if(is_array($languages) && count($languages))
                                        {{ implode(', ', $languages) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Location:</th>
                                <td>
                                    @php
                                        $primaryAddress = $user->addresses->first();
                                        $geoProfile = $user->partnerProfile;
                                    @endphp
                                    @if($primaryAddress)
                                        {{ trim(($primaryAddress->city ?? '') . ', ' . ($primaryAddress->state ?? ''), ', ') }}
                                        <small class="text-muted d-block">{{ $primaryAddress->addressLine }}</small>
                                    @elseif(!empty($geoProfile?->latitude) && !empty($geoProfile?->longitude))
                                        <span class="text-monospace small">{{ $geoProfile->latitude }}, {{ $geoProfile->longitude }}</span>
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                            </tr>
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
                    'Aadhar Card' => optional($partnerProfile)->aadhar_card,
                    'PAN Card' => optional($partnerProfile)->pan_card,
                    'Police Verification' => optional($partnerProfile)->police_verification,
                    'Covid Certificate' => optional($partnerProfile)->covid_vaccination_certificate,
                    'Selfie with Costume' => optional($partnerProfile)->selfie_with_costume,
                    'Intro Video' => optional($partnerProfile)->intro_video,
                ];
            @endphp

            @foreach($docs as $label => $file)
                <div class="col-md-4 mb-4 text-center">
                    <p class="fw-bold mb-1">{{ $label }}</p>
                    @if($file)
                        @php 
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)); 
                            $fileUrl = asset('storage/'.$file);
                        @endphp

                        {{-- Image Preview --}}
                        @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                            <a href="{{ $fileUrl }}" target="_blank">
                                <img src="{{ $fileUrl }}" 
                                     class="img-fluid rounded border shadow-sm preview-image" 
                                     style="max-height: 180px; object-fit: cover; cursor: pointer;">
                            </a>

                        {{-- Video Preview --}}
                        @elseif(in_array($ext, ['mp4','mov','avi','mkv','webm']))
                            <a href="{{ $fileUrl }}" target="_blank">
                                <video class="rounded border shadow-sm preview-video" 
                                       style="width: 100%; max-height: 180px; cursor: pointer;" muted>
                                    <source src="{{ $fileUrl }}" type="video/{{ $ext }}">
                                    Your browser does not support the video tag.
                                </video>
                            </a>

                        {{-- PDF or Other Docs --}}
                        @elseif(in_array($ext, ['pdf']))
                            <a href="{{ $fileUrl }}" target="_blank" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-file-pdf me-1"></i> View {{ $label }}
                            </a>
                        @else
                            <a href="{{ $fileUrl }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-download me-1"></i> Open {{ $label }}
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
<style>
.preview-image, .preview-video {
    transition: transform 0.2s ease-in-out;
}
.preview-image:hover, .preview-video:hover {
    transform: scale(1.05);
}
</style>

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

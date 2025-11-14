@extends('admin.layouts.app')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Create Zone</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.zones.index') }}">Zones</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@include('admin.layouts.messages')

<section class="content">
    <div class="container-fluid">
        @include('admin.zones.partials.form', [
            'zone' => $zone,
            'formAction' => route('admin.zones.store'),
            'httpMethod' => 'POST',
            'submitLabel' => 'Save Zone',
        ])
    </div>
</section>
@endsection

@section('customJS')
@php
    $initialCoordinates = old('coordinates')
        ? json_decode(old('coordinates'), true)
        : ($zone->coordinates ?? []);
@endphp
<script>
    window.zoneMapConfig = {
        center: { lat: 12.9716, lng: 77.5946, zoom: 11 },
        initialCoordinates: @json($initialCoordinates),
        readonly: false,
    };
</script>
@include('admin.zones.partials.map-script')
<script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&libraries=places,drawing&callback=initZoneManager" async defer></script>
@endsection


@extends('admin.layouts.app')

@section('content')
@include('admin.layouts.messages')

<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Notification Details</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ $notification->title }}</h3>
            </div>
            <div class="card-body">
                <p><strong>Topic:</strong> {{ $notification->topic }}</p>
                <p><strong>Body:</strong> {{ $notification->body }}</p>
                <p><strong>Sent:</strong> {{ $notification->sent ? 'Yes' : 'No' }}</p>
                <p><strong>Custom Data:</strong>
                <pre>{{ json_encode($notification->data, JSON_PRETTY_PRINT) }}</pre>
                </p>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </div>
</section>
@endsection
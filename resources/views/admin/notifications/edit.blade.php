@extends('admin.layouts.app')

@section('content')
@include('admin.layouts.messages')

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Edit Notification</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.notifications.index') }}">Notifications</a></li>
                    <li class="breadcrumb-item active">Edit Notification</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Edit Notification Details</h3>
            </div>

            <form action="{{ route('admin.notifications.update', $notification->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card-body">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $notification->title) }}" required>
                    </div>

                    <div class="form-group">
                        <label>Body</label>
                        <textarea name="body" class="form-control" rows="3" required>{{ old('body', $notification->body) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Topic</label>
                        <select name="topic" class="form-control" required>
                            <option value="">Select Topic</option>
                            <option value="all" {{ old('topic', $notification->topic)=='all' ? 'selected' : '' }}>All</option>
                            <option value="providers" {{ old('topic', $notification->topic)=='providers' ? 'selected' : '' }}>Providers</option>
                            <option value="users" {{ old('topic', $notification->topic)=='users' ? 'selected' : '' }}>Users</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Custom Data (JSON)</label>
                        <textarea name="data" class="form-control" rows="2" placeholder='{"key":"value"}'>{{ old('data', json_encode($notification->data)) }}</textarea>
                        <small class="form-text text-muted">Optional. Enter valid JSON, e.g. <code>{"order_id":123,"type":"promo"}</code></small>
                    </div>
                </div>

                <div class="card-footer">
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Notification</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@extends('admin.layouts.app')

@section('content')
@include('admin.layouts.messages')

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Add Notification</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.notifications.index') }}">Notifications</a></li>
                    <li class="breadcrumb-item active">Add Notification</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Notification Details</h3>
            </div>

            <form action="{{ route('admin.notifications.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                    </div>

                    <div class="form-group">
                        <label>Body</label>
                        <textarea name="body" class="form-control" rows="3" required>{{ old('body') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Topic</label>
                        <select name="topic" class="form-control" required>
                            <option value="">Select Topic</option>
                            <option value="all" {{ old('topic')=='all' ? 'selected' : '' }}>All</option>
                            <option value="providers" {{ old('topic')=='providers' ? 'selected' : '' }}>Providers</option>
                            <option value="users" {{ old('topic')=='users' ? 'selected' : '' }}>Users</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Custom Data (JSON)</label>
                        <textarea name="data" class="form-control" rows="2" placeholder='{"key":"value"}'>{{ old('data') }}</textarea>
                        <small class="form-text text-muted">Optional. Enter valid JSON, e.g. <code>{"order_id":123,"type":"promo"}</code></small>
                    </div>
                </div>

                <div class="card-footer">
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Notification</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

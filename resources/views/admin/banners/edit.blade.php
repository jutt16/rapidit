@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Edit Banner</h1>

    @include('admin.layouts.messages')

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.banners.update', $banner) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="form-group mb-3">
                    <label for="title">Title</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $banner->title) }}" required>
                </div>

                <div class="form-group mb-3">
                    <label for="description">Description</label>
                    <textarea name="description" class="form-control">{{ old('description', $banner->description) }}</textarea>
                </div>

                <div class="form-group mb-3">
                    <label for="image">Media (Image / Video)</label>
                    <input type="file" name="image" class="form-control" accept="image/*,video/*">
                    <small class="form-text text-muted">Supported: JPG, PNG, GIF, WEBP, MP4, MOV, AVI, WEBM (max 50MB)</small>
                    @if($banner->image)
                        @if($banner->media_type === 'video')
                            <video width="150" controls class="mt-2">
                                <source src="{{ asset('storage/'.$banner->image) }}" type="{{ $banner->mime_type ?? 'video/mp4' }}">
                                Your browser does not support the video tag.
                            </video>
                        @else
                            <img src="{{ asset('storage/'.$banner->image) }}" width="150" class="mt-2" alt="Banner media preview">
                        @endif
                    @endif
                </div>

                <div class="form-group mb-3">
                    <label for="status">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" {{ old('status', $banner->status) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status', $banner->status) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Update Banner</button>
                <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary">Back</a>
            </form>
        </div>
    </div>
</div>
@endsection
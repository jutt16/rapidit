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
                    <label for="image">Image</label>
                    <input type="file" name="image" class="form-control">
                    @if($banner->image)
                    <img src="{{ asset('storage/'.$banner->image) }}" width="150" class="mt-2">
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
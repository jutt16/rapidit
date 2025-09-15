@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Create Banner</h1>

    @include('admin.layouts.messages')

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group mb-3">
                    <label for="title">Title</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                </div>

                <div class="form-group mb-3">
                    <label for="description">Description</label>
                    <textarea name="description" class="form-control">{{ old('description') }}</textarea>
                </div>

                <div class="form-group mb-3">
                    <label for="image">Image</label>
                    <input type="file" name="image" class="form-control" required>
                </div>

                <div class="form-group mb-3">
                    <label for="status">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" {{ old('status') == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status') == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Create Banner</button>
                <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary">Back</a>
            </form>
        </div>
    </div>
</div>
@endsection
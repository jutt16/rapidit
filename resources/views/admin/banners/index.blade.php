@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Banners</h1>
        <a href="{{ route('admin.banners.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Banner
        </a>
    </div>

    @include('admin.layouts.messages')

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($banners as $index => $banner)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><img src="{{ asset('storage/'.$banner->image) }}" width="80"></td>
                        <td>{{ $banner->title }}</td>
                        <td>{{ $banner->description }}</td>
                        <td>
                            @if($banner->status)
                            <span class="badge bg-success">Active</span>
                            @else
                            <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>

                            <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this banner?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">No banners found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
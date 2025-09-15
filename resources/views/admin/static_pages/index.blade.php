@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">📄 Manage Static Pages</h2>
    </div>

    @include('admin.layouts.messages')

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Slug</th>
                        <th>Title</th>
                        <th>Last Updated</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pages as $index => $page)
                    <tr>
                        <td>{{ $index+1 }}</td>
                        <td><span class="badge bg-secondary">{{ $page->slug }}</span></td>
                        <td class="fw-semibold">{{ $page->title }}</td>
                        <td>{{ $page->updated_at ? $page->updated_at->format('d M, Y h:i A') : '-' }}</td>
                        <td class="text-center">
                            <a href="{{ route('admin.static-pages.edit', $page->id) }}"
                                class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil-square"></i> Edit
                            </a>
                        </td>
                    </tr>
                    @endforeach

                    @if($pages->isEmpty())
                    <tr>
                        <td colspan="5" class="text-center text-muted">No static pages found.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@extends('admin.layouts.app')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Services</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Services</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@include('admin.layouts.messages')

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Service List</h3>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Tax</th>
                            <th>Total (Price + Tax)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $index => $service)
                        <tr>
                            <td>{{ $services->firstItem() + $index }}</td>
                            <td>{{ $service->category?->name ?? '-' }}</td>
                            <td>{{ $service->name }}</td>
                            <td>{{ number_format($service->price, 2) }}</td>
                            <td>{{ number_format($service->tax, 2) }}</td>
                            <td>{{ number_format($service->price_with_tax, 2) }}</td>
                            <td>
                                <a href="{{ route('admin.services.edit', $service) }}"
                                    class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No services found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer clearfix">
                {{ $services->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
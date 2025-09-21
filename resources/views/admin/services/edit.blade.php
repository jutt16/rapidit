@extends('admin.layouts.app')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Edit Service</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.services.index') }}">Services</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@include('admin.layouts.messages')

<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <form action="{{ route('admin.services.update', $service) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label>Service Name</label>
                        <input type="text" class="form-control" value="{{ $service->name }}" disabled>
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" class="form-control" value="{{ $service->category?->name ?? '-' }}" disabled>
                    </div>

                    <div class="form-group">
                        <label>Price</label>
                        <input type="number" step="0.01" name="price" class="form-control"
                            value="{{ old('price', $service->price) }}" required>
                    </div>

                    <div class="form-group">
                        <label>Tax</label>
                        <input type="number" step="0.01" name="tax" class="form-control"
                            value="{{ old('tax', $service->tax) }}" required>
                    </div>

                    <div class="form-group">
                        <label>Commission (%)</label>
                        <input type="number" step="0.01" name="commission_pct" class="form-control"
                            value="{{ old('commission_pct', $service->commission_pct) }}" required>
                    </div>

                    <div class="form-group">
                        <label>Cancellation Charges</label>
                        <input type="number" step="0.01" name="cancellation_charges" class="form-control"
                            value="{{ old('cancellation_charges', $service->cancellation_charges) }}" required>
                    </div>

                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update Service</button>
                    <a href="{{ route('admin.services.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
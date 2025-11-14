@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>Maid Packages</h1>

    @include('admin.layouts.messages')

    <div class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4 col-md-5 col-sm-6">
                <a href="{{ route('admin.maid-pricings.create') }}" class="btn btn-primary w-100">
                    <i class="fas fa-plus"></i> Add Package
                </a>
            </div>
            <div class="col-lg-8 col-md-7 col-sm-6">
                <form method="GET" action="{{ route('admin.maid-pricings.index') }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-7">
                            <label for="sort" class="form-label text-muted text-uppercase small mb-1">Sort Packages</label>
                            <select name="sort" id="sort" class="form-select form-select-sm">
                                <option value="price_asc" {{ ($sort ?? request('sort')) === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                                <option value="price_desc" {{ ($sort ?? request('sort')) === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                                <option value="time_asc" {{ ($sort ?? request('sort')) === 'time_asc' ? 'selected' : '' }}>Duration: Short to Long</option>
                                <option value="time_desc" {{ ($sort ?? request('sort')) === 'time_desc' ? 'selected' : '' }}>Duration: Long to Short</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="submit" class="btn btn-primary btn-sm">
                                Apply
                            </button>
                        </div>
                        <div class="col-md-2 d-grid">
                            <a href="{{ route('admin.maid-pricings.index') }}" class="btn btn-outline-secondary btn-sm {{ (($sort ?? request('sort')) && ($sort ?? request('sort')) !== 'price_asc') ? '' : 'disabled' }}">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Time (minutes)</th>
                <th>Price (₹)</th>
                <th>Discount (%)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($packages as $package)
            <tr>
                <td>{{ $package->time }}</td>
                <td>{{ $package->price }}</td>
                <td>{{ $package->discount }}</td>
                <td>
                    <a href="{{ route('admin.maid-pricings.edit', $package) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('admin.maid-pricings.destroy', $package) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

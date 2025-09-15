@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>{{ isset($maidPricing) ? 'Edit' : 'Add' }} Maid Package</h1>

    @include('admin.layouts.messages')

    <form action="{{ isset($maidPricing) ? route('admin.maid-pricings.update', $maidPricing) : route('admin.maid-pricings.store') }}" method="POST">
        @csrf
        @if(isset($maidPricing))
            @method('PUT')
        @endif

        <div class="form-group">
            <label>Time (minutes)</label>
            <input type="number" name="time" class="form-control" value="{{ $maidPricing->time ?? old('time') }}" required>
        </div>

        <div class="form-group">
            <label>Price (₹)</label>
            <input type="number" step="0.01" name="price" class="form-control" value="{{ $maidPricing->price ?? old('price') }}" required>
        </div>

        <div class="form-group">
            <label>Discount (%)</label>
            <input type="number" step="0.01" name="discount" class="form-control" value="{{ $maidPricing->discount ?? old('discount') }}" required>
        </div>

        <button type="submit" class="btn btn-primary">{{ isset($maidPricing) ? 'Update' : 'Add' }}</button>
    </form>
</div>
@endsection

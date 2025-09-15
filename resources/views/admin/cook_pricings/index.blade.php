@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>Cook Pricing</h1>

    @include('admin.layouts.messages')

    <form action="{{ route('admin.cook-pricings.save') }}" method="POST">
        @csrf

        <div class="form-group">
            <label>Base Price (₹) for 1 person, up to 2 dishes</label>
            <input type="number" step="0.01" name="base_price" class="form-control" value="{{ $pricing->base_price ?? 150 }}">
        </div>

        <div class="form-group">
            <label>Additional Dish Charge (₹) per dish above 2</label>
            <input type="number" step="0.01" name="additional_dish_charge" class="form-control" value="{{ $pricing->additional_dish_charge ?? 50 }}">
        </div>

        <div class="form-group">
            <label>Additional Person Charge (%) per extra person</label>
            <input type="number" step="0.01" name="additional_person_percentage" class="form-control" value="{{ $pricing->additional_person_percentage ?? 40 }}">
        </div>

        <button type="submit" class="btn btn-primary">Save Pricing</button>
    </form>
</div>
@endsection

@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>Maid Packages</h1>

    @include('admin.layouts.messages')

    <a href="{{ route('admin.maid-pricings.create') }}" class="btn btn-primary mb-3">Add Package</a>

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

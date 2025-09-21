@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Provider Search Radius</h1>

    {{-- flash / validation messages --}}
    @include('admin.layouts.messages')

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.settings.radius.update') }}" method="POST" class="form-horizontal">
                @csrf
                @method('PUT')

                <div class="form-group row">
                    <label for="provider_radius" class="col-sm-2 col-form-label">
                        Radius (km)
                    </label>
                    <div class="col-sm-4">
                        <input
                            type="number"
                            name="provider_radius"
                            id="provider_radius"
                            class="form-control @error('provider_radius') is-invalid @enderror"
                            value="{{ old('provider_radius', $radius ?? 10) }}"
                            step="0.1"
                            min="0.1"
                            placeholder="Enter distance in km"
                            required>
                        @error('provider_radius')
                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">
                            Partners within this radius (in kilometres) will receive booking requests.
                        </small>
                    </div>
                </div>

                <div class="form-group row mt-3">
                    <div class="col-sm-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save
                        </button>
                        <a href="{{ url()->previous() }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
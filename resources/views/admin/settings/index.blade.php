@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <h1>App Settings</h1>

    @include('admin.layouts.messages')

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Provider Radius --}}
                <div class="form-group row">
                    <label for="provider_radius" class="col-sm-2 col-form-label">Provider Radius (km)</label>
                    <div class="col-sm-4">
                        <input type="number" name="provider_radius" id="provider_radius"
                            class="form-control @error('provider_radius') is-invalid @enderror"
                            value="{{ old('provider_radius', $radius) }}" step="0.1" min="0.1" required>
                        @error('provider_radius')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">
                            Providers within this distance will receive booking requests.
                        </small>
                    </div>
                </div>

                {{-- Initial Discount --}}
                <div class="form-group row mt-3">
                    <label for="initial_discount" class="col-sm-2 col-form-label">Initial Discount (%)</label>
                    <div class="col-sm-4">
                        <input type="number" name="initial_discount" id="initial_discount"
                            class="form-control @error('initial_discount') is-invalid @enderror"
                            value="{{ old('initial_discount', $initial_discount) }}" step="0.1" min="0" max="100" required>
                        @error('initial_discount')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">
                            This discount will be applied to new users' first booking.
                        </small>
                    </div>
                </div>

                <div class="form-group row mt-4">
                    <div class="col-sm-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
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

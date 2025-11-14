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

                {{-- Application Timezone --}}
                <div class="form-group row mt-3">
                    <label for="app_timezone" class="col-sm-2 col-form-label">Application Timezone</label>
                    <div class="col-sm-4">
                        <select name="app_timezone" id="app_timezone"
                            class="form-control @error('app_timezone') is-invalid @enderror" required>
                            @foreach($timezones as $timezone)
                                <option value="{{ $timezone['value'] }}" {{ old('app_timezone', $app_timezone) === $timezone['value'] ? 'selected' : '' }}>
                                    {{ $timezone['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('app_timezone')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">
                            All system dates and times will use this timezone.
                        </small>
                    </div>
                </div>

                {{-- Current Time Preview --}}
                <div class="form-group row mt-2">
                    <label class="col-sm-2 col-form-label">Current Time</label>
                    <div class="col-sm-4">
                        <input type="text" id="current_time_preview" class="form-control" value="{{ $current_time }}" readonly>
                        <small class="form-text text-muted">
                            Updates live for the selected timezone. Save to apply globally.
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

@section('customJS')
<script>
    (function () {
        const timezoneSelect = document.getElementById('app_timezone');
        const currentTimeInput = document.getElementById('current_time_preview');

        if (!timezoneSelect || !currentTimeInput) {
            return;
        }

        const formatDate = (date, timeZone) => {
            try {
                const parts = new Intl.DateTimeFormat('en-GB', {
                    timeZone,
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false,
                }).formatToParts(date);

                const get = (type) => parts.find((part) => part.type === type)?.value || '';

                return `${get('year')}-${get('month')}-${get('day')} ${get('hour')}:${get('minute')}:${get('second')}`;
            } catch (error) {
                return 'Invalid timezone';
            }
        };

        const updatePreview = () => {
            const tz = timezoneSelect.value;
            currentTimeInput.value = formatDate(new Date(), tz);
        };

        timezoneSelect.addEventListener('change', updatePreview);
        updatePreview();
        setInterval(updatePreview, 1000);
    })();
</script>
@endsection

<form method="POST" action="{{ $formAction }}">
    @csrf
    @if($httpMethod !== 'POST')
        @method($httpMethod)
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card card-outline card-primary h-100">
                <div class="card-body">
                    <div class="form-group">
                        <label for="zone-name">Zone Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="zone-name" class="form-control"
                            value="{{ old('name', $zone->name) }}" required>
                        @error('name')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="zone-description">Description</label>
                        <textarea name="description" id="zone-description" rows="3" class="form-control"
                            placeholder="Optional notes visible to admins only">{{ old('description', $zone->description) }}</textarea>
                        @error('description')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="zone-color">Polygon Color</label>
                        <input type="color" name="color" id="zone-color" class="form-control"
                            value="{{ old('color', $zone->color ?? '#FF7043') }}">
                        @error('color')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <input type="hidden" name="is_active" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="zone-status"
                                {{ old('is_active', $zone->is_active ?? true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="zone-status">Active zone</label>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Inactive zones stay in history but will be hidden from apps.
                        </small>
                        @error('is_active')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-1"></i>{{ $submitLabel }}
                    </button>
                    <a href="{{ route('admin.zones.index') }}" class="btn btn-default btn-block">
                        Cancel
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-outline card-primary mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-map-marked-alt mr-1"></i>
                        Draw polygon
                    </h3>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" id="reset-zone">
                            <i class="fas fa-undo mr-1"></i>Clear polygon
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label for="zone-search">Search location</label>
                        <input type="text" id="zone-search" class="form-control" placeholder="Search city, area or landmark">
                    </div>

                    <div class="alert alert-light border mb-3">
                        <ul class="mb-0 pl-3 small">
                            <li>Click the polygon icon on the map toolbar and outline the service boundary.</li>
                            <li>Close the polygon by clicking on the starting point.</li>
                            <li>Drag vertices to fine-tune or click to add new points.</li>
                        </ul>
                    </div>

                    <div id="zonesMap" style="height: 500px;"></div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Polygon points:</strong>
                        <span id="zone-point-count" class="badge badge-info">0</span>
                    </div>
                    <pre id="zone-coordinates-preview" class="bg-light p-3 rounded small mb-0" style="max-height: 200px; overflow-y: auto;">[]</pre>
                    <input type="hidden" name="coordinates" id="zone-coordinates"
                        value="{{ old('coordinates', $zone->coordinates ? json_encode($zone->coordinates) : '[]') }}">
                    @error('coordinates')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</form>


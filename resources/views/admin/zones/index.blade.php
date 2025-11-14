@extends('admin.layouts.app')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Service Zones</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Zones</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@include('admin.layouts.messages')

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Defined Zones</h3>
                <a href="{{ route('admin.zones.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-map-marked-alt mr-1"></i>
                    Add Zone
                </a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Color</th>
                            <th>Points</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($zones as $zone)
                            <tr>
                                <td>{{ $zones->firstItem() + $loop->index }}</td>
                                <td>
                                    <strong>{{ $zone->name }}</strong>
                                    @if($zone->description)
                                        <div class="text-muted small">
                                            {{ \Illuminate\Support\Str::limit($zone->description, 60) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge px-3 py-2" style="background: {{ $zone->color ?? '#ccc' }};">
                                        {{ $zone->color ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>{{ count($zone->coordinates ?? []) }}</td>
                                <td>
                                    @if($zone->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $zone->updated_at?->format('d M Y, H:i') }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.zones.edit', $zone) }}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.zones.destroy', $zone) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm"
                                            onclick="return confirm('Are you sure you want to delete this zone?');">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    No zones defined yet. Start by creating one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                {{ $zones->links() }}
            </div>
        </div>
    </div>
</section>
@endsection


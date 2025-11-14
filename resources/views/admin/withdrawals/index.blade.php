@extends('admin.layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Withdrawals</h1>
        <a href="{{ route('admin.withdrawals.export', request()->query()) }}" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export CSV
        </a>
    </div>

    @include('admin.layouts.messages')

    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Expert ID</th>
                <th>Amount</th>
                <th>Fee</th>
                <th>Bank</th>
                <th>Account</th>
                <th>UTR</th>
                <th>Status</th>
                <th>Requested At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($withdrawals as $w)
            <tr>
                <td>{{ $w->id }}</td>
                <td>{{ $w->user->name }} ({{ $w->user->email }})</td>
                <td>#{{ $w->user->id }}</td>
                <td>{{ number_format($w->amount,2) }} {{ $w->currency }}</td>
                <td>{{ number_format($w->fee,2) }}</td>
                <td>{{ $w->bankingDetail->bank_name ?? '-' }}</td>
                <td>{{ $w->bankingDetail->masked_account() ?? '****' }}</td>
                <td>{{ $w->utr ?? '-' }}</td>
                <td>
                    <span class="badge 
                        @if($w->status=='pending') bg-secondary
                        @elseif($w->status=='processing') bg-warning
                        @elseif($w->status=='completed') bg-success
                        @elseif($w->status=='rejected') bg-danger
                        @else bg-info @endif">
                        {{ ucfirst($w->status) }}
                    </span>
                </td>
                <td>{{ $w->created_at->format('Y-m-d H:i') }}</td>
                <td>
                    <div class="small mb-2">
                        <div><strong>UTR:</strong> {{ $w->utr ?? '—' }}</div>
                        <div><strong>Expert ID:</strong> #{{ $w->user->id }}</div>
                        <div><strong>Settlement Time:</strong> {{ $w->processed_at ? \Illuminate\Support\Carbon::parse($w->processed_at)->format('Y-m-d H:i') : '—' }}</div>
                        <div><strong>Reference:</strong> {{ $w->reference ?? '—' }}</div>
                    </div>
                    <a href="{{ route('admin.withdrawals.show', $w->id) }}" class="btn btn-sm btn-info mb-1">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $withdrawals->links() }}
</div>
@endsection

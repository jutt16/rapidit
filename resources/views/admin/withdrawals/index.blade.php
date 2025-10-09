@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>Withdrawals</h1>

    @include('admin.layouts.messages')

    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Amount</th>
                <th>Fee</th>
                <th>Bank</th>
                <th>Account</th>
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
                <td>{{ number_format($w->amount,2) }} {{ $w->currency }}</td>
                <td>{{ number_format($w->fee,2) }}</td>
                <td>{{ $w->bankingDetail->bank_name ?? '-' }}</td>
                <td>{{ $w->bankingDetail->masked_account() ?? '****' }}</td>
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
                    <a href="{{ route('admin.withdrawals.show', $w->id) }}" class="btn btn-sm btn-info mb-1">View</a>

                    <form action="{{ route('admin.withdrawals.approve', $w->id) }}" method="POST" style="display:inline;">
                        @csrf
                        <button class="btn btn-sm btn-success mb-1" onclick="return confirm('Approve and pay withdrawal #{{ $w->id }}?')">Approve & Pay</button>
                    </form>

                    <form action="{{ route('admin.withdrawals.reject', $w->id) }}" method="POST" style="display:inline;">
                        @csrf
                        <button class="btn btn-sm btn-danger mb-1" onclick="return confirm('Reject withdrawal #{{ $w->id }}? This will refund the user.')">Reject</button>
                    </form>

                    <form action="{{ route('admin.withdrawals.markPaid', $w->id) }}" method="POST" style="display:inline;">
                        @csrf
                        <input type="hidden" name="transaction_id" value="MANUAL-{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(6)) }}">
                        <button class="btn btn-sm btn-primary mb-1" onclick="return confirm('Mark withdrawal #{{ $w->id }} as paid?')">Mark Paid</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $withdrawals->links() }}
</div>
@endsection

@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>Withdrawal #{{ $w->id }}</h1>

    @include('admin.layouts.messages')

    <div class="card">
        <div class="card-body">
            <p><strong>User:</strong> {{ $w->user->name }} ({{ $w->user->email }})</p>
            <p><strong>Amount:</strong> {{ number_format($w->amount,2) }} {{ $w->currency }}</p>
            <p><strong>Fee:</strong> {{ number_format($w->fee,2) }}</p>
            <p><strong>Bank:</strong> {{ $w->bankingDetail->bank_name ?? '-' }}</p>
            <p><strong>Account:</strong> {{ $w->bankingDetail->masked_account() ?? '****' }}</p>
            <p><strong>Status:</strong> {{ ucfirst($w->status) }}</p>
            <p><strong>Reference:</strong> {{ $w->reference }}</p>
            <p><strong>Admin Note:</strong> {{ $w->admin_note }}</p>
            <p><strong>Requested at:</strong> {{ $w->created_at }}</p>
        </div>
    </div>

    <div class="mt-3">
        <form action="{{ route('admin.withdrawals.approve', $w->id) }}" method="POST" style="display:inline;">
            @csrf
            <button class="btn btn-success" onclick="return confirm('Approve & pay now?')">Approve & Pay</button>
        </form>

        <form action="{{ route('admin.withdrawals.reject', $w->id) }}" method="POST" style="display:inline;">
            @csrf
            <input type="hidden" name="reason" value="Rejected by admin via dashboard">
            <button class="btn btn-danger" onclick="return confirm('Reject & refund?')">Reject & Refund</button>
        </form>

        <form action="{{ route('admin.withdrawals.markPaid', $w->id) }}" method="POST" style="display:inline;">
            @csrf
            <input type="text" name="transaction_id" placeholder="Provider transaction id" required>
            <button class="btn btn-primary" onclick="return confirm('Mark paid?')">Mark Paid</button>
        </form>

        <a href="{{ route('admin.withdrawals.index') }}" class="btn btn-secondary">Back</a>
    </div>
</div>
@endsection

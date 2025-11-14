@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>Withdrawal #{{ $w->id }}</h1>

    @include('admin.layouts.messages')

    <div class="card">
        <div class="card-body">
            <p><strong>User:</strong> {{ $w->user->name }} ({{ $w->user->email }})</p>
            <p><strong>Expert ID:</strong> #{{ $w->user->id }}</p>
            <p><strong>Amount:</strong> {{ number_format($w->amount,2) }} {{ $w->currency }}</p>
            <p><strong>Fee:</strong> {{ number_format($w->fee,2) }}</p>
            <p><strong>Bank:</strong> {{ $w->bankingDetail->bank_name ?? '-' }}</p>
            <p><strong>Account:</strong> {{ $w->bankingDetail->masked_account() ?? '****' }}</p>
            <p><strong>Status:</strong> {{ ucfirst($w->status) }}</p>
            <p><strong>Reference:</strong> {{ $w->reference }}</p>
            <p><strong>UTR:</strong> {{ $w->utr ?? '—' }}</p>
            <p><strong>Gateway Status:</strong> {{ $w->gateway_status ?? '—' }}</p>
            <p><strong>Admin Note:</strong> {{ $w->admin_note }}</p>
            <p><strong>Requested at:</strong> {{ $w->created_at }}</p>
            <p><strong>Settlement Time:</strong> {{ $w->processed_at ? \Illuminate\Support\Carbon::parse($w->processed_at)->format('Y-m-d H:i') : '—' }}</p>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('admin.withdrawals.index') }}" class="btn btn-secondary">Back</a>
    </div>
</div>
@endsection

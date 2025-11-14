@extends('admin.layouts.app')

@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Dashboard</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

@include('admin.layouts.messages')

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Small boxes (Stat box) -->
        <!-- Cards WITH Links -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $totalUsers }}</h3>
                        <p>Total Users</p>
                    </div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <a href="{{ route('admin.users.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $totalPartners }}</h3>
                        <p>Partners</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-tie"></i></div>
                    <a href="{{ route('admin.partners.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $totalServices }}</h3>
                        <p>Services</p>
                    </div>
                    <div class="icon"><i class="fas fa-concierge-bell"></i></div>
                    <a href="{{ route('admin.services.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $totalBookings }}</h3>
                        <p>Total Bookings</p>
                    </div>
                    <div class="icon"><i class="fas fa-calendar-check"></i></div>
                    <a href="{{ route('admin.bookings.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ $completedBookings }}</h3>
                        <p>Completed Bookings</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <a href="{{ route('admin.bookings.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-light">
                    <div class="inner">
                        <h3>{{ $pendingWithdrawals }}</h3>
                        <p>Pending/Processing Withdrawals</p>
                    </div>
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                    <a href="{{ route('admin.withdrawals.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $completedWithdrawals }}</h3>
                        <p>Completed Withdrawals</p>
                    </div>
                    <div class="icon"><i class="fas fa-check"></i></div>
                    <a href="{{ route('admin.withdrawals.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h3 class="card-title mb-0">Recent Withdrawals</h3></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>User</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>UTR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentWithdrawals as $withdrawal)
                                        <tr>
                                            <td>{{ $withdrawal->id }}</td>
                                            <td>{{ $withdrawal->user?->name ?? 'N/A' }}</td>
                                            <td>₹{{ number_format($withdrawal->amount, 2) }}</td>
                                            <td>{{ ucfirst($withdrawal->status) }}</td>
                                            <td>{{ $withdrawal->utr ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">No withdrawals yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards WITHOUT Links -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-teal">
                    <div class="inner">
                        <h3>{{ number_format($totalRevenue, 2) }}</h3>
                        <p>Total Revenue</p>
                    </div>
                    <div class="icon"><i class="fas fa-rupee-sign"></i></div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-indigo">
                    <div class="inner">
                        <h3>{{ number_format($platformProfit, 2) }}</h3>
                        <p>Platform Profit</p>
                    </div>
                    <div class="icon"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-dark">
                    <div class="inner">
                        <h3>{{ number_format($totalPayouts, 2) }}</h3>
                        <p>Total Payouts</p>
                    </div>
                    <div class="icon"><i class="fas fa-university"></i></div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        <!-- Charts Row -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Bookings (Last 30 days)</h3></div>
                    <div class="card-body">
                        <canvas id="chartBookings"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Services Distribution</h3></div>
                    <div class="card-body">
                        <canvas id="chartServices"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Revenue (Last 30 days)</h3></div>
                    <div class="card-body">
                        <canvas id="chartRevenue"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Payouts (Last 30 days)</h3></div>
                    <div class="card-body">
                        <canvas id="chartPayouts"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->
@endsection

@section('customJS')
<script>
    $(document).ready(function() {
        function daysArray(n) {
            const arr = [];
            const d = new Date();
            d.setHours(0,0,0,0);
            for (let i = n - 1; i >= 0; i--) {
                const dd = new Date(d);
                dd.setDate(d.getDate() - i);
                arr.push(dd.toISOString().slice(0,10));
            }
            return arr;
        }

        const labels = daysArray(30);
        const bookingsByDay = @json($bookingsByDay);
        const revenueByDay = @json($revenueByDay);
        const payoutsByDay = @json($payoutsByDay);

        const bookingsData = labels.map(l => Number(bookingsByDay[l] || 0));
        const revenueData = labels.map(l => Number(revenueByDay[l] || 0));
        const payoutsData = labels.map(l => Number(payoutsByDay[l] || 0));

        if (window.Chart) {
            const ctxB = document.getElementById('chartBookings').getContext('2d');
            new Chart(ctxB, {
                type: 'line',
                data: { labels, datasets: [{ label: 'Bookings', data: bookingsData, borderColor: '#17a2b8', backgroundColor: 'rgba(23,162,184,0.1)', tension: 0.3 }] },
                options: { responsive: true, scales: { y: { beginAtZero: true } } }
            });

            const ctxR = document.getElementById('chartRevenue').getContext('2d');
            new Chart(ctxR, {
                type: 'bar',
                data: { labels, datasets: [{ label: 'Revenue (INR)', data: revenueData, backgroundColor: '#28a745' }] },
                options: { responsive: true, scales: { y: { beginAtZero: true } } }
            });

            const ctxP = document.getElementById('chartPayouts').getContext('2d');
            new Chart(ctxP, {
                type: 'bar',
                data: { labels, datasets: [{ label: 'Payouts (INR)', data: payoutsData, backgroundColor: '#343a40' }] },
                options: { responsive: true, scales: { y: { beginAtZero: true } } }
            });

            const services = @json($serviceDistribution);
            const svcLabels = services.map(s => s.name);
            const svcValues = services.map(s => Number(s.c));
            const ctxS = document.getElementById('chartServices').getContext('2d');
            new Chart(ctxS, {
                type: 'doughnut',
                data: { labels: svcLabels, datasets: [{ data: svcValues, backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545','#17a2b8','#6f42c1'] }] },
                options: { responsive: true }
            });
        }
    });
</script>
@endsection
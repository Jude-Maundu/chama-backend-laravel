@extends('layouts.app')

@section('title', 'Contributions')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2">Contributions</h1>
                @if(Auth::user()->role === 'admin' || Auth::user()->role === 'treasurer')
                    <a href="{{ route('contributions.create') }}" class="btn btn-primary">Record Contribution</a>
                @endif
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Amount</h6>
                    <h3 class="card-text">KES {{ number_format($totalAmount ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Completed</h6>
                    <h3 class="card-text">{{ $completedCount ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Pending</h6>
                    <h3 class="card-text">{{ $pendingCount ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Count</h6>
                    <h3 class="card-text">{{ count($contributions) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Contribution History</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contributions as $contribution)
                        <tr>
                            <td>
                                <a href="{{ route('members.show', $contribution->user) }}">
                                    {{ $contribution->user->name }}
                                </a>
                            </td>
                            <td>KES {{ number_format($contribution->total_amount, 2) }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($contribution->payment_method) }}</span></td>
                            <td>{{ $contribution->payment_date->format('M d, Y') }}</td>
                            <td>
                                <span class="badge bg-{{ $contribution->status === 'completed' ? 'success' : 'warning' }}">
                                    {{ ucfirst($contribution->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('contributions.history', $contribution->user) }}" class="btn btn-sm btn-info">View History</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No contributions yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $contributions->links() }}
    </div>

    <div class="mt-4">
        <a href="{{ route('contributions.report') }}" class="btn btn-secondary">View Reports</a>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Loans')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2">Loans</h1>
                @if(Auth::user()->role === 'member')
                    <a href="{{ route('loans.apply') }}" class="btn btn-primary">Apply for Loan</a>
                @endif
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Active Loans</h6>
                    <h3 class="card-text">{{ $activeLoans ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Disbursed</h6>
                    <h3 class="card-text">KES {{ number_format($totalDisbursed ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Loan Applications</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Applied</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                        <tr>
                            <td>{{ $loan->user->name }}</td>
                            <td>KES {{ number_format($loan->amount, 2) }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($loan->loan_type) }}</span></td>
                            <td>
                                <span class="badge bg-{{ $loan->status === 'approved' ? 'success' : ($loan->status === 'pending' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($loan->status) }}
                                </span>
                            </td>
                            <td>{{ $loan->application_date->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('loans.show', $loan) }}" class="btn btn-sm btn-info">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No loan applications</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $loans->links() }}
    </div>
@endsection

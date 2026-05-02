@extends('layouts.app')

@section('title', 'Treasurer Dashboard')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h2">Treasurer Dashboard</h1>
            <p class="text-muted">Financial management and reporting dashboard.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Collected</h6>
                    <h3 class="card-text">KES {{ number_format($totalCollected ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Pending Payments</h6>
                    <h3 class="card-text">{{ $pendingPayments ?? 0 }}</h3>
                </div>
            </div>
        </div>
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
                    <h6 class="card-title text-muted">Fund Balance</h6>
                    <h3 class="card-text">KES {{ number_format($balance ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="btn-group" role="group">
                <a href="{{ route('contributions.report') }}" class="btn btn-primary">View Reports</a>
                <a href="{{ route('contributions.create') }}" class="btn btn-secondary">Record Contribution</a>
                <a href="{{ route('logout') }}" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
@endsection
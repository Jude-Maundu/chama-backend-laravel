@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h2">Admin Dashboard</h1>
            <p class="text-muted">Welcome to the Chama System administration panel.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Members</h6>
                    <h3 class="card-text">{{ $totalMembers ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Contributions</h6>
                    <h3 class="card-text">{{ $totalContributions ?? 0 }}</h3>
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
                    <h6 class="card-title text-muted">Total Assets</h6>
                    <h3 class="card-text">KES {{ number_format($totalAssets ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('logout') }}" class="btn btn-danger">Logout</a>
    </div>
@endsection
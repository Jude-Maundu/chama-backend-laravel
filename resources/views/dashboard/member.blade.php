@extends('layouts.app')

@section('title', 'Member Dashboard')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h2">My Dashboard</h1>
            <p class="text-muted">Welcome to the Chama System member portal.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">My Contribution</h6>
                    <h3 class="card-text">KES {{ number_format($myContribution ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">My Share</h6>
                    <h3 class="card-text">{{ $myShare ?? 0 }}%</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">My Loans</h6>
                    <h3 class="card-text">{{ $myLoans ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Dividends Received</h6>
                    <h3 class="card-text">KES {{ number_format($dividends ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="btn-group" role="group">
                <a href="{{ route('contributions.create') }}" class="btn btn-primary">Make Contribution</a>
                <a href="{{ route('loans.apply') }}" class="btn btn-secondary">Apply for Loan</a>
                <a href="{{ route('logout') }}" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
@endsection
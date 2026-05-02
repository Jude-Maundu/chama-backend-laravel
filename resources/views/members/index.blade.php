@extends('layouts.app')

@section('title', 'Members')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2">Members Directory</h1>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Members</h6>
                    <h3 class="card-text">{{ $totalMembers }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Active</h6>
                    <h3 class="card-text">{{ $activeMembers }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control" placeholder="Search members..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @forelse($members as $member)
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $member->name }}</h5>
                        <p class="card-text text-muted">{{ $member->email }}</p>
                        @if($member->profile)
                            <p class="card-text small">{{ $member->profile->bio ?? 'No bio' }}</p>
                        @endif
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Contributions: KES {{ number_format($member->contributions()->sum('total_amount'), 2) }}</small>
                            <small class="text-muted">Loans: {{ $member->loans()->count() }}</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('members.show', $member) }}" class="btn btn-sm btn-primary">View Profile</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">No members found</div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $members->links() }}
    </div>
@endsection

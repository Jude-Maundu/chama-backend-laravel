@extends('layouts.app')

@section('title', 'Financial Goals')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2">Financial Goals</h1>
                <a href="{{ route('goals.create') }}" class="btn btn-primary">Set Goal</a>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Target</h6>
                    <h3 class="card-text">KES {{ number_format($totalTarget, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Saved</h6>
                    <h3 class="card-text">KES {{ number_format($totalSaved, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Progress</h6>
                    <h3 class="card-text">{{ round($progressPercent, 1) }}%</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse($goals as $goal)
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $goal->goal_name }}</h5>
                        <p class="card-text small text-muted">{{ $goal->goal_description }}</p>
                        <div class="progress mb-2">
                            <div class="progress-bar" style="width: {{ min(($goal->current_amount / $goal->target_amount) * 100, 100) }}%"></div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <small>Saved: KES {{ number_format($goal->current_amount, 2) }}</small>
                            </div>
                            <div class="col-6 text-end">
                                <small>Target: KES {{ number_format($goal->target_amount, 2) }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('goals.contribute', $goal) }}" class="btn btn-sm btn-primary">Contribute</a>
                        <a href="{{ route('goals.delete', $goal) }}" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">No goals set yet. <a href="{{ route('goals.create') }}">Create your first goal</a></div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $goals->links() }}
    </div>
@endsection

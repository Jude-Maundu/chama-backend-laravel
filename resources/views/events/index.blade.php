@extends('layouts.app')

@section('title', 'Events')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2">Events</h1>
                @if(Auth::user()->role === 'admin')
                    <a href="{{ route('events.create') }}" class="btn btn-primary">Create Event</a>
                @endif
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Upcoming</h6>
                    <h3 class="card-text">{{ $upcomingCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Attending</h6>
                    <h3 class="card-text">{{ $attendingCount }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse($events as $event)
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $event->title }}</h5>
                        <p class="card-text">{{ $event->description }}</p>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="bi bi-calendar"></i> {{ $event->date->format('M d, Y') }}
                                </small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="bi bi-geo"></i> {{ $event->location }}
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('events.show', $event) }}" class="btn btn-sm btn-primary">View Details</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">No upcoming events</div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $events->links() }}
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Meetings')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2">Meetings</h1>
                @if(Auth::user()->role === 'admin')
                    <a href="{{ route('meetings.create') }}" class="btn btn-primary">Schedule Meeting</a>
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
                    <h6 class="card-title text-muted">Completed</h6>
                    <h3 class="card-text">{{ $completedCount }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Scheduled Meetings</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($meetings as $meeting)
                        <tr>
                            <td>{{ $meeting->title }}</td>
                            <td><span class="badge bg-info">{{ $meeting->meeting_type }}</span></td>
                            <td>{{ $meeting->scheduled_date->format('M d, Y @ H:i') }}</td>
                            <td>{{ $meeting->location }}</td>
                            <td>
                                <span class="badge bg-{{ $meeting->status === 'completed' ? 'success' : 'warning' }}">
                                    {{ ucfirst($meeting->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-sm btn-info">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No meetings scheduled</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $meetings->links() }}
    </div>
@endsection

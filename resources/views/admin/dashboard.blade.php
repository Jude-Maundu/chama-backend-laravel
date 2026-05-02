@extends('layouts.app')

@section('content')
<div class="container-fluid mt-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="bi bi-shield-lock"></i> Admin Dashboard</h2>
            <p class="text-muted">Manage all system settings and configurations</p>
        </div>
    </div>

    <!-- Admin Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="text-muted">Total Chamas</h6>
                    <h3 class="text-primary">{{ $totalChamas ?? 12 }}</h3>
                    <small>{{ $activeChamas ?? 11 }} active</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-muted">Total Members</h6>
                    <h3 class="text-success">{{ $totalMembers ?? 342 }}</h3>
                    <small>{{ $verifiedMembers ?? 320 }} verified</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-muted">Pending Approvals</h6>
                    <h3 class="text-warning">{{ $pendingApprovals ?? 8 }}</h3>
                    <small>Needs review</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="text-muted">System Health</h6>
                    <h3 class="text-info">98%</h3>
                    <small>All systems operational</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Tools -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-tools"></i> System Settings</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('admin.settings') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-gear"></i> General Settings
                        <i class="bi bi-chevron-right float-end"></i>
                    </a>
                    <a href="{{ route('admin.roles') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-shield"></i> Manage Roles & Permissions
                        <i class="bi bi-chevron-right float-end"></i>
                    </a>
                    <a href="{{ route('admin.users') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-people"></i> User Management
                        <i class="bi bi-chevron-right float-end"></i>
                    </a>
                    <a href="{{ route('admin.compliance') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-check-circle"></i> Compliance & Audit
                        <i class="bi bi-chevron-right float-end"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-database"></i> Data Management</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('admin.backup') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-cloud-download"></i> Backup & Restore
                        <i class="bi bi-chevron-right float-end"></i>
                    </a>
                    <a href="{{ route('admin.logs') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-file-earmark-text"></i> System Logs
                        <i class="bi bi-chevron-right float-end"></i>
                    </a>
                    <a href="{{ route('admin.integration') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-link"></i> API Integrations
                        <i class="bi bi-chevron-right float-end"></i>
                    </a>
                    <a href="{{ route('admin.reports') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-bar-chart"></i> System Reports
                        <i class="bi bi-chevron-right float-end"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card mt-4">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent System Activity</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Resource</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2024-01-15 14:30:22</td>
                        <td>John Admin</td>
                        <td>User Created</td>
                        <td>New Member Registration</td>
                        <td><span class="badge bg-success">Success</span></td>
                    </tr>
                    <tr>
                        <td>2024-01-15 13:15:45</td>
                        <td>System</td>
                        <td>Backup Completed</td>
                        <td>Daily Database Backup</td>
                        <td><span class="badge bg-success">Success</span></td>
                    </tr>
                    <tr>
                        <td>2024-01-15 12:05:10</td>
                        <td>Jane Treasurer</td>
                        <td>Loan Approved</td>
                        <td>Loan ID: 456</td>
                        <td><span class="badge bg-success">Success</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

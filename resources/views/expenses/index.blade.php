@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="bi bi-receipt"></i> Expenses Management</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('expenses.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Record Expense
            </a>
        </div>
    </div>

    <!-- Expense Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="text-muted">Total Expenses</h6>
                    <h3 class="text-info">KES {{ number_format($totalExpenses ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-muted">This Month</h6>
                    <h3 class="text-warning">KES {{ number_format($monthExpenses ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-muted">Pending Approval</h6>
                    <h3 class="text-success">{{ $pendingExpenses ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body">
                    <h6 class="text-muted">Budget Remaining</h6>
                    <h3 class="text-danger">KES {{ number_format($budgetRemaining ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="category" class="form-control" placeholder="Filter by category">
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search description">
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Expenses Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Reported By</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2024-01-15</td>
                        <td><span class="badge bg-info">Meeting Venue</span></td>
                        <td>Hall rental for monthly meeting</td>
                        <td>KES 2,000</td>
                        <td>John Doe</td>
                        <td><span class="badge bg-success">Approved</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>2024-01-14</td>
                        <td><span class="badge bg-warning">Supplies</span></td>
                        <td>Office stationery and forms</td>
                        <td>KES 800</td>
                        <td>Jane Smith</td>
                        <td><span class="badge bg-warning">Pending</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>2024-01-13</td>
                        <td><span class="badge bg-secondary">Admin</span></td>
                        <td>Bank charges for January</td>
                        <td>KES 500</td>
                        <td>Treasurer</td>
                        <td><span class="badge bg-success">Approved</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#">3</a></li>
        </ul>
    </nav>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="bi bi-graph-up"></i> Financial Report</h2>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-outline-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
            <button class="btn btn-outline-success"><i class="bi bi-download"></i> Export PDF</button>
        </div>
    </div>

    <!-- Period Selector -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Report Type</label>
                    <select class="form-select">
                        <option>Monthly Summary</option>
                        <option>Quarterly Report</option>
                        <option>Annual Report</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><i class="bi bi-search"></i> Generate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h6 class="text-muted">Total Income</h6>
                    <h3 class="text-success">KES 1,250,000</h3>
                    <small class="text-muted">+5% vs last period</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-danger">
                <div class="card-body">
                    <h6 class="text-muted">Total Expenses</h6>
                    <h3 class="text-danger">KES 180,000</h3>
                    <small class="text-muted">-2% vs last period</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-info">
                <div class="card-body">
                    <h6 class="text-muted">Net Surplus</h6>
                    <h3 class="text-info">KES 1,070,000</h3>
                    <small class="text-muted">+8% vs last period</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <h6 class="text-muted">Members Count</h6>
                    <h3 class="text-warning">145</h3>
                    <small class="text-muted">Active members</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Reports -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-pie-chart"></i> Income Breakdown</h6>
                </div>
                <div class="card-body">
                    <div style="height: 300px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                        <p class="text-muted">Chart placeholder</p>
                    </div>
                    <table class="table table-sm mt-3">
                        <tr>
                            <td>Contributions</td>
                            <td class="text-end">KES 800,000</td>
                        </tr>
                        <tr>
                            <td>Loan Repayments</td>
                            <td class="text-end">KES 300,000</td>
                        </tr>
                        <tr>
                            <td>Investment Returns</td>
                            <td class="text-end">KES 150,000</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Expense Breakdown</h6>
                </div>
                <div class="card-body">
                    <div style="height: 300px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                        <p class="text-muted">Chart placeholder</p>
                    </div>
                    <table class="table table-sm mt-3">
                        <tr>
                            <td>Meeting Costs</td>
                            <td class="text-end">KES 60,000</td>
                        </tr>
                        <tr>
                            <td>Administration</td>
                            <td class="text-end">KES 80,000</td>
                        </tr>
                        <tr>
                            <td>Other</td>
                            <td class="text-end">KES 40,000</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trend -->
    <div class="card mt-4">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-graph-up"></i> Monthly Trend</h6>
        </div>
        <div class="card-body">
            <div style="height: 250px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                <p class="text-muted">Trend chart placeholder</p>
            </div>
        </div>
    </div>
</div>
@endsection

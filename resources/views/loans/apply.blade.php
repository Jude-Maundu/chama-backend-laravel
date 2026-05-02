@extends('layouts.app')

@section('title', 'Apply for Loan')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Loan Application Form</h5>
                </div>
                <div class="card-body">
                    @if($eligibilityScore)
                        <div class="alert alert-info">
                            <strong>Eligibility Score:</strong> {{ round($eligibilityScore->score, 2) }}/100
                            <br>
                            <strong>Maximum Loan Amount:</strong> KES {{ number_format($maxLoanAmount, 2) }}
                        </div>
                    @endif

                    <form action="{{ route('loans.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label">Loan Type</label>
                            <select name="loan_type" class="form-control @error('loan_type') is-invalid @enderror" required>
                                <option value="">Select Type</option>
                                <option value="personal">Personal</option>
                                <option value="business">Business</option>
                                <option value="emergency">Emergency</option>
                            </select>
                            @error('loan_type')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Loan Amount (KES)</label>
                            <input type="number" name="amount" step="0.01" class="form-control @error('amount') is-invalid @enderror" required>
                            @error('amount')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Duration (Months)</label>
                            <input type="number" name="duration_months" min="1" max="60" class="form-control @error('duration_months') is-invalid @enderror" required>
                            @error('duration_months')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Purpose</label>
                            <textarea name="purpose" class="form-control @error('purpose') is-invalid @enderror" rows="3" required></textarea>
                            @error('purpose')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Apply for Loan</button>
                            <a href="{{ route('loans.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

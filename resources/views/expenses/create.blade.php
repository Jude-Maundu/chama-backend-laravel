@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h2 class="mb-4"><i class="bi bi-plus-circle"></i> Record New Expense</h2>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data">
                        @csrf

                        <!-- Expense Date -->
                        <div class="mb-3">
                            <label for="date" class="form-label">Expense Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" required>
                            @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Category -->
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                                <option value="">Select category...</option>
                                <option value="1">Meeting Venue</option>
                                <option value="2">Supplies</option>
                                <option value="3">Administration</option>
                                <option value="4">Training</option>
                                <option value="5">Transportation</option>
                                <option value="6">Other</option>
                            </select>
                            @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" required></textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Amount -->
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (KES) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" step="0.01" placeholder="0.00" required>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Paid To/Beneficiary -->
                        <div class="mb-3">
                            <label for="paid_to" class="form-label">Paid To <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('paid_to') is-invalid @enderror" id="paid_to" name="paid_to" placeholder="Name/Business" required>
                            @error('paid_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Receipt/Proof -->
                        <div class="mb-3">
                            <label for="receipt" class="form-label">Receipt/Proof <span class="text-danger">*</span></label>
                            <input type="file" class="form-control @error('receipt') is-invalid @enderror" id="receipt" name="receipt" accept="image/*,.pdf">
                            <small class="text-muted">Upload receipt or proof of expense (Image or PDF)</small>
                            @error('receipt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check"></i> Record Expense</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

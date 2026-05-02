@extends('layouts.app')

@section('title', 'Record Contribution')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Record New Contribution</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('contributions.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label">Member</label>
                            <select name="user_id" class="form-control @error('user_id') is-invalid @enderror" required>
                                <option value="">Select Member</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('user_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Amount (KES)</label>
                            <input type="number" name="amount" step="0.01" class="form-control @error('amount') is-invalid @enderror" required>
                            @error('amount')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-control @error('payment_method') is-invalid @enderror" required>
                                <option value="">Select Method</option>
                                <option value="mpesa">M-Pesa</option>
                                <option value="cash">Cash</option>
                                <option value="bank">Bank Transfer</option>
                            </select>
                            @error('payment_method')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Record Contribution</button>
                            <a href="{{ route('contributions.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h2 class="mb-4"><i class="bi bi-person-circle"></i> Profile Settings</h2>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <!-- Profile Picture -->
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <img src="https://via.placeholder.com/150" alt="Profile" class="rounded-circle" width="150" height="150">
                            </div>
                            <input type="file" class="form-control" name="avatar">
                            <small class="text-muted">JPG or PNG, max 2MB</small>
                        </div>

                        <!-- Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ Auth::user()->name ?? '' }}" required>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ Auth::user()->email ?? '' }}" required>
                        </div>

                        <!-- Phone -->
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="{{ Auth::user()->phone ?? '' }}">
                        </div>

                        <!-- Location -->
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location">
                        </div>

                        <!-- Bio -->
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"></textarea>
                        </div>

                        <hr>

                        <!-- Password Change -->
                        <h5>Change Password</h5>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>

                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation">
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

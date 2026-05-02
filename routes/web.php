<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\MemberController;
use App\Http\Controllers\Web\ContributionController;
use App\Http\Controllers\Web\LoanController;
use App\Http\Controllers\Web\MeetingController;
use App\Http\Controllers\Web\EventController;
use App\Http\Controllers\Web\FinancialGoalController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\MpesaController;
use App\Http\Controllers\Web\AdminController;

// Public routes
Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// M-Pesa callback (public)
Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');

// Protected routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->name('dashboard.admin');
    Route::get('/dashboard/treasurer', [DashboardController::class, 'treasurer'])->name('dashboard.treasurer');
    Route::get('/dashboard/member', [DashboardController::class, 'member'])->name('dashboard.member');
    
    // Members
    Route::resource('members', MemberController::class);
    Route::get('members/{member}/approve', [MemberController::class, 'approve'])->name('members.approve');
    Route::get('members/pending/list', [MemberController::class, 'pending'])->name('members.pending');
    Route::get('profile/edit', [MemberController::class, 'editProfile'])->name('profile.edit');
    Route::put('profile/update', [MemberController::class, 'updateProfile'])->name('profile.update');
    Route::post('profile/skills', [MemberController::class, 'addSkill'])->name('skills.add');
    Route::delete('skills/{skill}', [MemberController::class, 'deleteSkill'])->name('skills.delete');
    
    // Contributions
    Route::resource('contributions', ContributionController::class);
    Route::get('contributions/history/{user}', [ContributionController::class, 'history'])->name('contributions.history');
    Route::get('contributions-report', [ContributionController::class, 'report'])->name('contributions.report');
    
    // Loans
    Route::resource('loans', LoanController::class);
    Route::get('loans/apply', [LoanController::class, 'apply'])->name('loans.apply');
    Route::post('loans/{loan}/approve', [LoanController::class, 'approve'])->name('loans.approve');
    Route::post('loans/{loan}/disburse', [LoanController::class, 'disburse'])->name('loans.disburse');
    Route::get('loans/{loan}/repay', [LoanController::class, 'repayForm'])->name('loans.repay.form');
    Route::post('loans/{loan}/repay', [LoanController::class, 'repay'])->name('loans.repay');
    
    // Meetings
    Route::resource('meetings', MeetingController::class);
    Route::post('meetings/{meeting}/decision', [MeetingController::class, 'addDecision'])->name('meetings.decision.add');
    Route::post('meetings/{meeting}/poll', [MeetingController::class, 'createPoll'])->name('meetings.poll.add');
    Route::post('polls/{poll}/vote', [MeetingController::class, 'votePoll'])->name('polls.vote');
    Route::post('meetings/{meeting}/attendance', [MeetingController::class, 'markAttendance'])->name('meetings.attendance');
    Route::post('meetings/{meeting}/report', [MeetingController::class, 'report'])->name('meetings.report');
    
    // Events
    Route::resource('events', EventController::class);
    Route::post('events/{event}/rsvp', [EventController::class, 'rsvp'])->name('events.rsvp');
    Route::post('events/{event}/attendance/{user}', [EventController::class, 'markAttendance'])->name('events.attendance');
    
    // Financial Goals
    Route::resource('goals', FinancialGoalController::class);
    Route::post('goals/{goal}/contribute', [FinancialGoalController::class, 'contribute'])->name('goals.contribute');
    Route::delete('goals/{goal}', [FinancialGoalController::class, 'delete'])->name('goals.delete');
    
    // Reports
    Route::get('reports/financial', [ReportController::class, 'financial'])->name('reports.financial');
    Route::get('reports/contributions', [ReportController::class, 'contributions'])->name('reports.contributions');
    Route::get('reports/loans', [ReportController::class, 'loans'])->name('reports.loans');
    
    // M-Pesa
    Route::get('mpesa/payment', [MpesaController::class, 'paymentForm'])->name('mpesa.payment');
    Route::post('mpesa/stkpush', [MpesaController::class, 'stkPush'])->name('mpesa.stkpush');
    Route::get('mpesa/status/{checkoutId}', [MpesaController::class, 'status'])->name('mpesa.status');
});

// Super Admin routes
Route::middleware(['auth', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/chamas', [SuperAdminController::class, 'chamas'])->name('chamas');
    Route::post('/chamas', [SuperAdminController::class, 'createChama'])->name('chamas.create');
    Route::get('/users', [SuperAdminController::class, 'users'])->name('users');
    Route::post('/users/{id}/make-super-admin', [SuperAdminController::class, 'makeSuperAdmin'])->name('users.make-super-admin');
    Route::post('/users/{id}/remove-super-admin', [SuperAdminController::class, 'removeSuperAdmin'])->name('users.remove-super-admin');
});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ContributionController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\DividendController;
use App\Http\Controllers\Api\InvestmentController;
use App\Http\Controllers\Api\MpesaController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\RecurringContributionController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\FinancialGoalController;
use App\Http\Controllers\Api\EmergencyFundController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\PettyCashController;
use App\Http\Controllers\Api\LoanEligibilityController;
use App\Http\Controllers\Api\BankReconciliationController;
use App\Http\Controllers\Api\MemberEngagementController;
use App\Http\Controllers\Api\ExitManagementController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\AdvancedMeetingController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\TaxController;
use App\Http\Controllers\Api\SecurityController;
use App\Http\Controllers\Api\AdminExtraController;
use App\Http\Controllers\Api\GamificationController;
use App\Http\Controllers\Api\IntegrationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/forgot-password', [AuthController::class, 'sendResetLink']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// M-Pesa webhook (public)
Route::post('/mpesa/callback', [MpesaController::class, 'callback']);
Route::post('/mpesa/stk-callback', [MpesaController::class, 'stkCallback']);

// Integration Hooks (Public)
Route::post('/ussd', [IntegrationController::class, 'ussd']);
Route::post('/whatsapp', [IntegrationController::class, 'whatsapp']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/auth/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // Phase 1: Member Engagement & Community
    Route::prefix('chamas/{chama}')->group(function () {
        // Member Engagement
        Route::get('/directory', [MemberEngagementController::class, 'getDirectory']);
        Route::get('/my-profile', [MemberEngagementController::class, 'getMyProfile']);
        Route::put('/profile', [MemberEngagementController::class, 'updateProfile']);
        Route::get('/skills', [MemberEngagementController::class, 'getSkills']);
        Route::post('/skills', [MemberEngagementController::class, 'addSkill']);
        Route::get('/referrals', [MemberEngagementController::class, 'getReferrals']);
        Route::post('/referrals/invite', [MemberEngagementController::class, 'inviteByEmail']);
        Route::get('/milestones', [MemberEngagementController::class, 'getMilestones']);
        Route::get('/challenges', [MemberEngagementController::class, 'getChallenges']);
        Route::post('/challenges/{challenge}/join', [MemberEngagementController::class, 'joinChallenge']);
        Route::get('/feedback', [MemberEngagementController::class, 'getFeedback']);
        Route::post('/feedback', [MemberEngagementController::class, 'submitFeedback']);
        Route::post('/feedback/{feedback}/vote', [MemberEngagementController::class, 'voteFeedback']);

        // Exit Management
        Route::get('/exit-requests', [ExitManagementController::class, 'getExitRequests']);
        Route::post('/exit-requests', [ExitManagementController::class, 'submitExitRequest']);
        Route::post('/exit-requests/{exitRequest}/process', [ExitManagementController::class, 'processExitRequest']);
        Route::get('/nominations', [ExitManagementController::class, 'getNominations']);
        Route::post('/nominations', [ExitManagementController::class, 'storeNomination']);
        Route::delete('/nominations/{nomination}', [ExitManagementController::class, 'deleteNomination']);

        // Community Features
        Route::get('/marketplace', [CommunityController::class, 'getMarketplaceItems']);
        Route::post('/marketplace', [CommunityController::class, 'storeMarketplaceItem']);
        Route::get('/insurance', [CommunityController::class, 'getInsuranceProducts']);
        Route::post('/insurance', [CommunityController::class, 'storeInsuranceProduct']);
        Route::get('/charity', [CommunityController::class, 'getCharityModules']);
        Route::post('/charity', [CommunityController::class, 'storeCharityModule']);
        Route::get('/jobs', [CommunityController::class, 'getJobPostings']);
        Route::post('/jobs', [CommunityController::class, 'storeJobPosting']);
        Route::get('/business-showcase', [CommunityController::class, 'getBusinessShowcases']);
        Route::post('/business-showcase', [CommunityController::class, 'storeBusinessShowcase']);

        // Phase 2: Enhanced Meetings & Events
        // Advanced Meetings
        Route::get('/meeting-templates', [AdvancedMeetingController::class, 'getTemplates']);
        Route::post('/meeting-templates', [AdvancedMeetingController::class, 'storeTemplate']);
        Route::get('/meetings/{meeting}/decisions', [AdvancedMeetingController::class, 'getDecisions']);
        Route::post('/meetings/{meeting}/decisions', [AdvancedMeetingController::class, 'storeDecision']);
        Route::put('/meeting-decisions/{decision}', [AdvancedMeetingController::class, 'updateDecision']);
        Route::get('/meetings/{meeting}/polls', [AdvancedMeetingController::class, 'getPolls']);
        Route::post('/meetings/{meeting}/polls', [AdvancedMeetingController::class, 'storePoll']);
        Route::post('/polls/{poll}/vote', [AdvancedMeetingController::class, 'votePoll']);

        // Events
        Route::get('/events', [EventController::class, 'index']);
        Route::post('/events', [EventController::class, 'store']);
        Route::get('/events/{event}', [EventController::class, 'show']);
        Route::post('/events/{event}/rsvp', [EventController::class, 'rsvp']);
        Route::post('/events/{event}/attendance/{attendee}', [EventController::class, 'markAttendance']);

        // Phase 4: Advanced Reporting & Analytics
        Route::get('/analytics/predictive', [AnalyticsController::class, 'predictive']);
        Route::get('/analytics/benchmarking', [AnalyticsController::class, 'benchmarking']);
        Route::get('/tax/summary', [TaxController::class, 'summary']);
        Route::post('/tax/calculate', [TaxController::class, 'calculateWithholding']);
        
        // From Phase 3 (Additional Report)
        Route::get('/reports/savings-projection', [ReportController::class, 'savingsProjection']);

        // Phase 5: Security, Compliance & System Admin
        Route::get('/security/audit-logs', [SecurityController::class, 'getAuditLogs']);
        Route::get('/security/compliance', [SecurityController::class, 'getComplianceChecklist']);
        Route::post('/security/compliance/{item}', [SecurityController::class, 'updateComplianceItem']);
        Route::get('/security/fraud-alerts', [SecurityController::class, 'getFraudAlerts']);
        
        Route::get('/admin/roles', [AdminExtraController::class, 'getRoles']);
        Route::post('/admin/roles', [AdminExtraController::class, 'storeRole']);
        Route::get('/admin/permissions', [AdminExtraController::class, 'getPermissions']);
        Route::get('/admin/webhooks', [AdminExtraController::class, 'getWebhooks']);
        Route::post('/admin/webhooks', [AdminExtraController::class, 'storeWebhook']);
        Route::delete('/admin/webhooks/{webhook}', [AdminExtraController::class, 'deleteWebhook']);
        Route::get('/admin/backup-settings', [AdminExtraController::class, 'getBackupSettings']);

        // Phase 6: Gamification, Mobile & Premium
        Route::get('/gamification/leaderboard', [GamificationController::class, 'leaderboard']);
        Route::get('/gamification/loyalty-points', [GamificationController::class, 'getLoyaltyPoints']);
        Route::post('/gamification/lottery-draw', [GamificationController::class, 'lotteryDraw']);
        Route::get('/gamification/badges', [GamificationController::class, 'getBadges']);

        Route::get('/admin/sms-credits', [IntegrationController::class, 'getSmsCredits']);
        Route::post('/admin/sms-credits', [IntegrationController::class, 'purchaseSmsCredits']);
    });

    // GDPR Tools (General)
    Route::get('/gdpr/export', [SecurityController::class, 'exportPersonalData']);
    Route::post('/gdpr/delete-request', [SecurityController::class, 'requestAccountDeletion']);

    // Educational Content (General)
    Route::get('/educational-content', [CommunityController::class, 'getEducationalContent']);
    Route::middleware(['admin'])->post('/educational-content', [CommunityController::class, 'storeEducationalContent']);

    // Chama Selection
    Route::get('/chamas/my-chamas', [App\Http\Controllers\Api\ChamaAdminController::class, 'getMyChamas']);
    Route::post('/chamas/{slug}/switch', [App\Http\Controllers\Api\ChamaAdminController::class, 'switchChama']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/upload-photo', [ProfileController::class, 'uploadPhoto']);
    Route::post('/profile/upload-document', [ProfileController::class, 'uploadDocument']);

    // Members
    Route::apiResource('/members', MemberController::class);
    Route::post('/members/{member}/activate', [MemberController::class, 'activate']);
    Route::post('/members/{member}/deactivate', [MemberController::class, 'deactivate']);
    Route::get('/members/{member}/statement', [MemberController::class, 'statement']);

    // Contributions
    Route::get('/contributions/history/{memberId?}', [ContributionController::class, 'history']);
    Route::get('/contributions/report', [ContributionController::class, 'report']);
    Route::get('/contributions/pending', [ContributionController::class, 'pending']);
    Route::apiResource('/contributions', ContributionController::class);
    Route::post('/contributions/{contribution}/pay', [ContributionController::class, 'payWithMpesa']);
    Route::get('/contributions/{contribution}/receipt', [ContributionController::class, 'downloadReceipt']);

    // Loans
    Route::apiResource('/loans', LoanController::class);
    Route::post('/loans/{loan}/apply', [LoanController::class, 'apply']);
    Route::post('/loans/{loan}/approve', [LoanController::class, 'approve']);
    Route::post('/loans/{loan}/reject', [LoanController::class, 'reject']);
    Route::post('/loans/{loan}/disburse', [LoanController::class, 'disburse']);
    Route::post('/loans/{loan}/repay', [LoanController::class, 'repay']);
    Route::get('/loans/{loan}/schedule', [LoanController::class, 'repaymentSchedule']);
    Route::get('/loans/{loan}/agreement', [LoanController::class, 'downloadAgreement']);

    // Meetings
    Route::apiResource('/meetings', MeetingController::class);
    Route::post('/meetings/{meeting}/attend', [MeetingController::class, 'markAttendance']);
    Route::post('/meetings/{meeting}/attendance', [MeetingController::class, 'markAttendance']);
    Route::post('/meetings/{meeting}/vote', [MeetingController::class, 'vote']);
    Route::get('/meetings/{meeting}/minutes', [MeetingController::class, 'downloadMinutes']);
    Route::post('/meetings/{meeting}/minutes', [MeetingController::class, 'uploadMinutes']);
    Route::post('/meetings/{meeting}/upload-minutes', [MeetingController::class, 'uploadMinutes']);

    // Dividends
    Route::get('/dividends/my-dividends', [DividendController::class, 'myDividends']);
    Route::post('/dividends/calculate', [DividendController::class, 'calculate']);
    Route::post('/dividends/{dividend}/distribute', [DividendController::class, 'distribute']);
    Route::apiResource('/dividends', DividendController::class);

    // Investments
    Route::get('/investments/performance', [InvestmentController::class, 'performance']);
    Route::post('/investments/{investment}/withdraw', [InvestmentController::class, 'withdraw']);
    Route::apiResource('/investments', InvestmentController::class);

    // Attendance
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/{attendance}', [AttendanceController::class, 'show']);

    // M-Pesa
    Route::get('/mpesa/transactions', [MpesaController::class, 'index']);
    Route::post('/mpesa/stk-push', [MpesaController::class, 'stkPush']);
    Route::get('/mpesa/balance', [MpesaController::class, 'checkBalance']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/preferences', [NotificationController::class, 'preferences']);
    Route::post('/notifications/preferences', [NotificationController::class, 'updatePreferences']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

    // Onboarding
    Route::get('/onboarding/check', [App\Http\Controllers\Api\OnboardingController::class, 'checkOnboarding']);
    Route::post('/onboarding/create', [App\Http\Controllers\Api\OnboardingController::class, 'createChama']);
    Route::post('/onboarding/join', [App\Http\Controllers\Api\OnboardingController::class, 'joinChama']);

    // Dashboard
    Route::get('/dashboard/admin', [DashboardController::class, 'adminDashboard']);
    Route::get('/dashboard/member', [DashboardController::class, 'memberDashboard']);
    Route::get('/dashboard/treasurer', [DashboardController::class, 'treasurerDashboard']);
    Route::get('/dashboard/secretary', [DashboardController::class, 'secretaryDashboard']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData']);

    // Reports
    Route::get('/reports/contributions', [ReportController::class, 'contributions']);
    Route::get('/reports/loans', [ReportController::class, 'loans']);
    Route::get('/reports/financial', [ReportController::class, 'financial']);
    Route::get('/reports/members', [ReportController::class, 'members']);
    Route::get('/reports/export/financial/{format}', [ReportController::class, 'exportFinancial']);
    Route::post('/reports/export', [ReportController::class, 'export']);

    // Settings (Admin only)
    Route::middleware(['admin'])->group(function () {
        Route::apiResource('/settings', SettingController::class);
        Route::post('/settings/bulk-update', [SettingController::class, 'bulkUpdate']);
    });

    // Phase 1: Financial Features
    // Currencies
    Route::get('/currencies', [CurrencyController::class, 'index']);
    Route::get('/currencies/{currency}', [CurrencyController::class, 'show']);
    Route::post('/currencies/convert', [CurrencyController::class, 'convert']);
    Route::middleware(['admin'])->post('/currencies/rates', [CurrencyController::class, 'updateExchangeRates']);

    // Recurring Contributions
    Route::get('/recurring-contributions', [RecurringContributionController::class, 'userContributions']);
    Route::prefix('chamas/{chama}')->group(function () {
        Route::get('/recurring-contributions', [RecurringContributionController::class, 'index']);
        Route::post('/recurring-contributions', [RecurringContributionController::class, 'store']);
        Route::put('/recurring-contributions/{contribution}', [RecurringContributionController::class, 'update']);
        Route::post('/recurring-contributions/{contribution}/pause', [RecurringContributionController::class, 'pause']);
        Route::post('/recurring-contributions/{contribution}/resume', [RecurringContributionController::class, 'resume']);
        Route::delete('/recurring-contributions/{contribution}', [RecurringContributionController::class, 'cancel']);

        // Wallets
        Route::get('/wallets', [WalletController::class, 'index']);
        Route::post('/wallets', [WalletController::class, 'store']);
        Route::get('/wallets/{wallet}', [WalletController::class, 'show']);
        Route::put('/wallets/{wallet}', [WalletController::class, 'update']);
        Route::post('/wallets/transfer', [WalletController::class, 'transfer']);
        Route::get('/wallets/{wallet}/transfers', [WalletController::class, 'transfers']);

        // Financial Goals
        Route::get('/financial-goals', [FinancialGoalController::class, 'index']);
        Route::post('/financial-goals', [FinancialGoalController::class, 'store']);
        Route::get('/financial-goals/{goal}', [FinancialGoalController::class, 'show']);
        Route::put('/financial-goals/{goal}', [FinancialGoalController::class, 'update']);
        Route::post('/financial-goals/{goal}/progress', [FinancialGoalController::class, 'addProgress']);
        Route::get('/financial-goals/{goal}/progress-detail', [FinancialGoalController::class, 'progress']);

        // Emergency Fund
        Route::get('/emergency-fund', [EmergencyFundController::class, 'show']);
        Route::middleware(['admin'])->post('/emergency-fund/initialize', [EmergencyFundController::class, 'initialize']);
        Route::post('/emergency-fund/add-funds', [EmergencyFundController::class, 'addFunds']);
        Route::post('/emergency-fund/request-withdrawal', [EmergencyFundController::class, 'requestWithdrawal']);
        Route::get('/emergency-fund/withdrawals/{withdrawal}', [EmergencyFundController::class, 'getWithdrawal']);
        Route::middleware(['admin'])->post('/emergency-fund/withdrawals/{withdrawal}/approve', [EmergencyFundController::class, 'approveWithdrawal']);
        Route::middleware(['admin'])->post('/emergency-fund/withdrawals/{withdrawal}/reject', [EmergencyFundController::class, 'rejectWithdrawal']);
        Route::middleware(['admin'])->post('/emergency-fund/withdrawals/{withdrawal}/process', [EmergencyFundController::class, 'processWithdrawal']);
        Route::get('/emergency-fund/pending-withdrawals', [EmergencyFundController::class, 'pendingWithdrawals']);

        // Expenses
        Route::get('/expenses', [ExpenseController::class, 'index']);
        Route::get('/expense-stats', [ExpenseController::class, 'stats']);
        Route::post('/expenses', [ExpenseController::class, 'store']);
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update']);
        Route::post('/expenses/{expense}/submit-approval', [ExpenseController::class, 'submitApproval']);
        Route::middleware(['admin'])->post('/expenses/{expense}/approve', [ExpenseController::class, 'approve']);
        Route::middleware(['admin'])->post('/expenses/{expense}/reject', [ExpenseController::class, 'reject']);
        Route::get('/expenses/categories', [ExpenseController::class, 'categories']);
        Route::middleware(['admin'])->post('/expenses/categories', [ExpenseController::class, 'storeCategory']);
        Route::get('/expenses/category/{category}/report', [ExpenseController::class, 'categoryReport']);

        // Petty Cash
        Route::get('/petty-cash', [PettyCashController::class, 'index']);
        Route::middleware(['admin'])->post('/petty-cash', [PettyCashController::class, 'store']);
        Route::get('/petty-cash/{account}', [PettyCashController::class, 'show']);
        Route::post('/petty-cash/{account}/claims', [PettyCashController::class, 'submitClaim']);
        Route::get('/petty-cash/{account}/pending-claims', [PettyCashController::class, 'pendingClaims']);
        Route::middleware(['admin'])->post('/petty-cash/{account}/claims/{claim}/approve', [PettyCashController::class, 'approveClaim']);
        Route::middleware(['admin'])->post('/petty-cash/{account}/claims/{claim}/reject', [PettyCashController::class, 'rejectClaim']);
        Route::middleware(['admin'])->post('/petty-cash/{account}/claims/{claim}/pay', [PettyCashController::class, 'payClaim']);
        Route::middleware(['admin'])->post('/petty-cash/{account}/reconcile', [PettyCashController::class, 'submitReconciliation']);
        Route::middleware(['admin'])->post('/petty-cash/{account}/reconciliations/{reconciliation}/approve', [PettyCashController::class, 'approveReconciliation']);

        // Loan Eligibility
        Route::get('/loan-eligibility/{user}', [LoanEligibilityController::class, 'show']);
        Route::get('/loan-eligibility', [LoanEligibilityController::class, 'indexForChama']);
        Route::middleware(['admin'])->post('/loan-eligibility/{user}/refresh', [LoanEligibilityController::class, 'refresh']);

        // Bank Reconciliation
        Route::get('/bank-reconciliation', [BankReconciliationController::class, 'index']);
        Route::middleware(['admin'])->post('/bank-reconciliation/upload', [BankReconciliationController::class, 'upload']);
        Route::get('/bank-reconciliation/{reconciliation}', [BankReconciliationController::class, 'show']);
        Route::get('/bank-reconciliation/{reconciliation}/results', [BankReconciliationController::class, 'results']);
        Route::middleware(['admin'])->post('/bank-reconciliation/{reconciliation}/approve', [BankReconciliationController::class, 'approve']);
        Route::get('/bank-reconciliation/{reconciliation}/discrepancies', [BankReconciliationController::class, 'discrepancies']);
    });

    // Chama Admin Routes
    Route::middleware(['admin'])->prefix('chama-admin')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Api\ChamaAdminController::class, 'dashboard']);
        
        // Members Management
        Route::get('/members', [App\Http\Controllers\Api\ChamaAdminController::class, 'getMembers']);
        Route::post('/members', [App\Http\Controllers\Api\ChamaAdminController::class, 'addMember']);
        Route::delete('/members/{memberId}', [App\Http\Controllers\Api\ChamaAdminController::class, 'removeMember']);
        
        // Loans Management
        Route::get('/loans', [App\Http\Controllers\Api\ChamaAdminController::class, 'getLoans']);
        Route::post('/loans/{loanId}/approve', [App\Http\Controllers\Api\ChamaAdminController::class, 'approveLoan']);
        Route::post('/loans/{loanId}/reject', [App\Http\Controllers\Api\ChamaAdminController::class, 'rejectLoan']);
        Route::post('/loans/{loanId}/disburse', [App\Http\Controllers\Api\ChamaAdminController::class, 'disburseLoan']);
        
        // Finances
        Route::get('/finances', [App\Http\Controllers\Api\ChamaAdminController::class, 'getFinances']);

        // Invitation codes
        Route::get('/invite-code', [App\Http\Controllers\Api\ChamaAdminController::class, 'getInviteCode']);
        Route::post('/invite-code/regenerate', [App\Http\Controllers\Api\ChamaAdminController::class, 'regenerateInviteCode']);
    });

    // Super Admin Routes
    Route::middleware(['auth:sanctum', 'superadmin'])->group(function () {
        Route::prefix('admin')->group(function () {
            // Dashboard
            Route::get('/dashboard-stats', [AdminController::class, 'dashboardStats']);

            // Users Management
            Route::get('/users', [AdminController::class, 'getUsers']);
            Route::post('/users', [AdminController::class, 'createUser']);
            Route::put('/users/{userId}', [AdminController::class, 'updateUser']);
            Route::delete('/users/{userId}', [AdminController::class, 'deleteUser']);

            // Chamas Management
            Route::get('/chamas', [AdminController::class, 'getChamas']);
            Route::post('/chamas', [AdminController::class, 'createChama']);
            Route::put('/chamas/{chamaId}', [AdminController::class, 'updateChama']);
            Route::delete('/chamas/{chamaId}', [AdminController::class, 'deleteChama']);

            // Audit Logs
            Route::get('/logs', [AdminController::class, 'getLogs']);
        });
    });

});

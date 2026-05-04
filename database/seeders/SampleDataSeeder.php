<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Chama;
use App\Models\ChamaMember;
use App\Models\Contribution;
use App\Models\Dividend;
use App\Models\DividendUser;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Meeting;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@chama.com')->first();

        if (!$admin) {
            return;
        }

        $chama = Chama::firstOrCreate([
            'slug' => 'demo-chama',
        ], [
            'name' => 'Demo Chama',
            'description' => 'A sample chama for initial testing.',
            'location' => 'Nairobi, Kenya',
            'email' => 'demo@chama.com',
            'phone' => '254700000001',
            'status' => 'active',
            'created_by' => $admin->id,
            'settings' => [
                'currency' => 'KES',
                'contribution_due_day' => 5,
            ],
        ]);

        ChamaMember::updateOrCreate([
            'chama_id' => $chama->id,
            'user_id' => $admin->id,
        ], [
            'role' => 'admin',
            'status' => 'active',
            'position' => 'Founder',
            'joined_at' => now()->toDateString(),
            'invited_by' => $admin->id,
        ]);

        Profile::updateOrCreate([
            'user_id' => $admin->id,
        ], [
            'national_id' => '123456789',
            'gender' => 'male',
            'dob' => '1990-01-01',
            'occupation' => 'System Administrator',
            'address' => '123 Demo Lane',
            'city' => 'Nairobi',
            'postal_code' => '00100',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '254700000001',
            'bank_name' => 'Demo Bank',
            'bank_account_number' => '1234567890',
            'mpesa_number' => '254700000001',
            'join_date' => now()->toDateString(),
        ]);

        Contribution::firstOrCreate([
            'transaction_id' => 'SEQ-2026-0001',
        ], [
            'user_id' => $admin->id,
            'amount' => 5000,
            'penalty' => 0,
            'total_amount' => 5000,
            'payment_method' => 'mpesa',
            'mpesa_receipt' => 'MPESA12345',
            'payment_date' => now()->toDateString(),
            'due_date' => now()->subDays(2)->toDateString(),
            'status' => 'completed',
            'receipt_number' => 'RCPT-0001',
            'notes' => 'Initial seed contribution',
            'recorded_by' => $admin->id,
        ]);

        $loan = Loan::firstOrCreate([
            'user_id' => $admin->id,
            'application_date' => now()->subDays(30)->toDateString(),
        ], [
            'loan_type' => 'emergency',
            'amount' => 10000,
            'interest_rate' => 10.00,
            'duration_months' => 6,
            'monthly_payment' => 1833.33,
            'total_payable' => 11000,
            'balance' => 11000,
            'penalty_amount' => 0,
            'purpose' => 'Seed loan for test data',
            'status' => 'approved',
            'approval_date' => now()->subDays(29)->toDateString(),
            'disbursement_date' => now()->subDays(28)->toDateString(),
            'first_payment_date' => now()->subDays(1)->toDateString(),
            'next_payment_date' => now()->addDays(29)->toDateString(),
            'approved_by' => $admin->id,
        ]);

        LoanRepayment::firstOrCreate([
            'loan_id' => $loan->id,
            'installment_number' => 1,
        ], [
            'due_amount' => 1833.33,
            'paid_amount' => 1833.33,
            'principal_paid' => 1666.67,
            'interest_paid' => 166.66,
            'penalty_paid' => 0,
            'balance_after' => 9166.67,
            'due_date' => now()->subDays(1)->toDateString(),
            'paid_date' => now()->toDateString(),
            'status' => 'paid',
            'transaction_id' => 'LOANPAY-0001',
            'payment_method' => 'mpesa',
        ]);

        $meeting = Meeting::firstOrCreate([
            'title' => 'Demo Chama Kickoff',
        ], [
            'description' => 'Initial demonstration meeting',
            'agenda' => 'Introduce demo data and validate workflows',
            'meeting_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'venue' => 'Demo Hall',
            'status' => 'scheduled',
            'created_by' => $admin->id,
        ]);

        Attendance::updateOrCreate([
            'meeting_id' => $meeting->id,
            'user_id' => $admin->id,
        ], [
            'status' => 'present',
            'arrival_time' => now()->format('H:i:s'),
            'signed_minutes' => false,
        ]);

        $dividend = Dividend::firstOrCreate([
            'period' => '2026-Q1',
        ], [
            'total_amount' => 2000,
            'total_shares' => 100,
            'per_share_amount' => 20,
            'calculation_date' => now()->subDays(10)->toDateString(),
            'distribution_date' => now()->addDays(5)->toDateString(),
            'status' => 'approved',
            'calculated_by' => $admin->id,
            'approved_by' => $admin->id,
        ]);

        DB::table('dividend_user')->updateOrInsert([
            'dividend_id' => $dividend->id,
            'user_id' => $admin->id,
        ], [
            'amount' => 2000,
            'status' => 'pending',
            'updated_at' => now(),
            'created_at' => now(),
        ]);
    }
}

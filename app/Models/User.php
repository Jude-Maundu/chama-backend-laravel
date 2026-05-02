<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'current_chama_id',
        'two_factor_enabled',
        'two_factor_code',
        'two_factor_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code',
    ];

    protected $appends = [
        'current_chama_role',
        'total_contributions',
        'total_savings',
        'total_loans',
        'outstanding_loan_balance',
        'photo_url',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'two_factor_enabled' => 'boolean',
    ];

    // Relationships
    public function currentChama()
    {
        return $this->belongsTo(Chama::class, 'current_chama_id');
    }

    public function chamas()
    {
        return $this->belongsToMany(Chama::class, 'chama_members')
                    ->withPivot('role', 'status', 'joined_at', 'position')
                    ->withTimestamps();
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function loanRepayments()
    {
        return $this->hasManyThrough(LoanRepayment::class, Loan::class);
    }

    public function meetings()
    {
        return $this->belongsToMany(Meeting::class, 'attendance')->withPivot('status', 'arrival_time');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function mpesaTransactions()
    {
        return $this->hasMany(MpesaTransaction::class);
    }

    public function dividends()
    {
        return $this->belongsToMany(Dividend::class, 'dividend_user')->withPivot('amount', 'status');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function guaranteedLoans()
    {
        return $this->belongsToMany(Loan::class, 'guarantors', 'guarantor_id', 'loan_id');
    }

    // Phase 1: Financial Feature Relationships
    public function recurringContributions()
    {
        return $this->hasMany(RecurringContribution::class);
    }

    public function wallets()
    {
        return $this->hasMany(MemberWallet::class);
    }

    public function walletTransfersFrom()
    {
        return $this->hasManyThrough(WalletTransfer::class, MemberWallet::class, 'user_id', 'from_wallet_id');
    }

    public function walletTransfersTo()
    {
        return $this->hasManyThrough(WalletTransfer::class, MemberWallet::class, 'user_id', 'to_wallet_id');
    }

    public function expenses()
    {
        return $this->hasMany(ChamaExpense::class, 'created_by');
    }

    public function approvedExpenses()
    {
        return $this->hasMany(ChamaExpense::class, 'approved_by');
    }

    public function pettyCashClaims()
    {
        return $this->hasMany(PettyCashClaim::class);
    }

    public function approvedClaims()
    {
        return $this->hasMany(PettyCashClaim::class, 'approved_by');
    }

    public function emergencyWithdrawals()
    {
        return $this->hasMany(EmergencyWithdrawal::class);
    }

    public function approvedWithdrawals()
    {
        return $this->hasMany(EmergencyWithdrawal::class, 'approved_by');
    }

    public function loanEligibilityScores()
    {
        return $this->hasMany(LoanEligibilityScore::class);
    }

    public function pettyCashCustodianAccounts()
    {
        return $this->hasMany(PettyCashAccount::class, 'custodian_id');
    }

    public function chamaMemberships()
    {
        return $this->belongsToMany(Chama::class, 'chama_members')
                   ->withPivot('role', 'status', 'joined_at', 'position')
                   ->withTimestamps();
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function getCurrentChamaRoleAttribute()
    {
        $chamaId = session('current_chama_id') ?? $this->current_chama_id;
        
        if (!$chamaId) {
            return null;
        }

        $membership = $this->chamas()->where('chama_id', $chamaId)->first();
        
        return $membership ? $membership->pivot->role : null;
    }

    public function getTotalContributionsAttribute()
    {
        return $this->contributions()->where('status', 'completed')->sum('total_amount');
    }

    public function getTotalSavingsAttribute()
    {
        // Savings = total contributions for now
        return $this->getTotalContributionsAttribute();
    }

    public function getTotalLoansAttribute()
    {
        return $this->loans()->sum('amount');
    }

    public function getOutstandingLoanBalanceAttribute()
    {
        return $this->loans()->whereNotIn('status', ['completed', 'rejected'])->sum('balance');
    }

    public function getPhotoUrlAttribute()
    {
        if ($this->profile && $this->profile->profile_photo) {
            return asset('storage/' . $this->profile->profile_photo);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMembers($query)
    {
        return $query->role('member');
    }
}
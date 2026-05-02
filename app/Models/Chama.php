<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chama extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'join_code', 'description', 'logo', 'cover_image',
        'location', 'email', 'phone', 'status', 'created_by', 'settings'
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->hasMany(ChamaMember::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'chama_members')
                    ->withPivot('role', 'status', 'joined_at', 'position')
                    ->withTimestamps();
    }

    public function admins()
    {
        return $this->users()->wherePivot('role', 'admin');
    }

    public function treasurers()
    {
        return $this->users()->wherePivot('role', 'treasurer');
    }

    public function activeMembers()
    {
        return $this->users()->wherePivot('status', 'active');
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function dividends()
    {
        return $this->hasMany(Dividend::class);
    }

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function invitations()
    {
        return $this->hasMany(ChamaInvitation::class);
    }

    // Phase 1: Financial Feature Relationships
    public function primaryCurrency()
    {
        return $this->belongsTo(Currency::class, 'primary_currency_id');
    }

    public function recurringContributions()
    {
        return $this->hasMany(RecurringContribution::class);
    }

    public function wallets()
    {
        return $this->hasMany(MemberWallet::class);
    }

    public function walletTransfers()
    {
        return $this->hasMany(WalletTransfer::class);
    }

    public function financialGoals()
    {
        return $this->hasMany(FinancialGoal::class);
    }

    public function emergencyFund()
    {
        return $this->hasOne(EmergencyFund::class);
    }

    public function expenseCategories()
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function expenses()
    {
        return $this->hasMany(ChamaExpense::class);
    }

    public function pettyCashAccounts()
    {
        return $this->hasMany(PettyCashAccount::class);
    }

    public function loanEligibilityScores()
    {
        return $this->hasMany(LoanEligibilityScore::class);
    }

    public function bankReconciliations()
    {
        return $this->hasMany(BankReconciliation::class);
    }

    public function getAccountBalance()
    {
        return $this->transactions()
                   ->where('type', 'credit')
                   ->sum('amount') - $this->transactions()
                                          ->where('type', 'debit')
                                          ->sum('amount');
    }

    public function isAdmin($userId)
    {
        return $this->members()->where('user_id', $userId)->where('role', 'admin')->exists();
    }

    public function isTreasurer($userId)
    {
        return $this->members()->where('user_id', $userId)->where('role', 'treasurer')->exists();
    }

    public function isMember($userId)
    {
        return $this->members()->where('user_id', $userId)->where('status', 'active')->exists();
    }

    public function getMemberRole($userId)
    {
        $member = $this->members()->where('user_id', $userId)->first();
        return $member ? $member->role : null;
    }

    public function getTotalSavings()
    {
        return $this->contributions()->where('status', 'completed')->sum('total_amount');
    }

    public function getTotalLoans()
    {
        return $this->loans()->sum('amount');
    }

    public function getOutstandingLoans()
    {
        return $this->loans()->whereIn('status', ['approved', 'disbursed'])->sum('balance');
    }
}

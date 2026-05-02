<?php

namespace App\Providers;

use App\Events\ContributionReceived;
use App\Events\LoanApproved;
use App\Events\LoanDisbursed;
use App\Events\MemberJoined;
use App\Events\MeetingScheduled;
use App\Events\DividendDistributed;
use App\Listeners\SendContributionNotification;
use App\Listeners\SendLoanApprovalNotification;
use App\Listeners\SendDividendNotification;
use App\Listeners\LogMemberActivity;
use App\Listeners\UpdateMemberStats;
use App\Listeners\ProcessLoanDisbursement;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ContributionReceived::class => [
            SendContributionNotification::class,
            UpdateMemberStats::class,
        ],
        LoanApproved::class => [
            SendLoanApprovalNotification::class,
            ProcessLoanDisbursement::class,
        ],
        LoanDisbursed::class => [],
        MemberJoined::class => [
            LogMemberActivity::class,
        ],
        MeetingScheduled::class => [],
        DividendDistributed::class => [
            SendDividendNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}

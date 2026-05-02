<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CheckOverdueContributions::class,
        Commands\SendContributionReminders::class,
        Commands\SendMeetingReminders::class,
        Commands\ProcessOverdueLoans::class,
        Commands\CalculateMonthlyInterest::class,
        Commands\BackupDatabase::class,
        Commands\CleanupOldLogs::class,
        Commands\GenerateMemberStatements::class,
        Commands\SyncMpesaTransactions::class,
        
        
        Commands\CheckSystemHealth::class,
        Commands\UpdateMemberStatus::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Daily tasks - Run at 8 AM
        $schedule->command('chama:send-contribution-reminders --sms')->dailyAt('08:00');
        $schedule->command('chama:check-overdue-contributions --apply-penalty --notify')->dailyAt('00:30');
        $schedule->command('chama:process-overdue-loans')->dailyAt('01:00');
        $schedule->command('chama:update-member-status')->dailyAt('02:00');
        
        // Hourly tasks
        $schedule->command('chama:sync-mpesa-transactions')->hourly();
        $schedule->command('chama:check-system-health')->hourly();
        
        // Weekly tasks - Sunday at midnight
        $schedule->command('chama:backup-database --compress')->weekly()->sundays()->at('00:00');
        $schedule->command('chama:cleanup-old-logs')->weekly()->sundays()->at('03:00');
        
        // Monthly tasks - 1st of month at 04:00
        $schedule->command('chama:calculate-monthly-interest --apply')->monthlyOn(1, '04:00');
        $schedule->command('chama:generate-member-statements')->monthlyOn(1, '05:00');
        
        // Meeting reminders - every 30 minutes
        $schedule->command('chama:send-meeting-reminders --sms')->everyThirtyMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        
        require base_path('routes/console.php');
    }
}
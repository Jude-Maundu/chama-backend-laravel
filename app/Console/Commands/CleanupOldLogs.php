<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Notification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupOldLogs extends Command
{
    protected $signature = 'chama:cleanup-old-logs
                            {--days=90 : Delete logs older than X days}
                            {--dry-run : Show what would be deleted without actually deleting}';
    
    protected $description = 'Clean up old audit logs and notifications';

    public function handle()
    {
        $this->info('🧹 Cleaning up old logs...');
        
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        // Count old audit logs
        $oldAuditLogs = AuditLog::where('created_at', '<', $cutoffDate);
        $auditCount = $oldAuditLogs->count();
        
        // Count old notifications
        $oldNotifications = Notification::where('created_at', '<', $cutoffDate)
            ->where('is_read', true);
        $notificationCount = $oldNotifications->count();
        
        $this->line("📋 Found {$auditCount} old audit log(s)");
        $this->line("📋 Found {$notificationCount} old notification(s)");
        
        if ($this->option('dry-run')) {
            $this->info('DRY RUN - No data was deleted');
            return Command::SUCCESS;
        }
        
        // Delete old data
        if ($auditCount > 0) {
            $oldAuditLogs->delete();
            $this->line("✅ Deleted {$auditCount} audit log(s)");
        }
        
        if ($notificationCount > 0) {
            $oldNotifications->delete();
            $this->line("✅ Deleted {$notificationCount} notification(s)");
        }
        
        $this->info('✅ Cleanup completed successfully!');
        
        return Command::SUCCESS;
    }
}
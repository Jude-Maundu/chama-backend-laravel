<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CheckSystemHealth extends Command
{
    protected $signature = 'chama:check-system-health
                            {--notify : Send notification if issues found}
                            {--fix : Attempt to fix common issues}';
    
    protected $description = 'Check system health and report any issues';

    public function handle()
    {
        $this->info('🏥 Running system health check...');
        
        $issues = [];
        
        // Check database connection
        $this->info('🔍 Checking database...');
        try {
            DB::connection()->getPdo();
            $this->line('   ✅ Database connection: OK');
        } catch (\Exception $e) {
            $issues[] = "Database connection failed: " . $e->getMessage();
            $this->error('   ❌ Database connection: FAILED');
        }
        
        // Check storage
        $this->info('🔍 Checking storage...');
        $testFile = storage_path('test.txt');
        try {
            file_put_contents($testFile, 'test');
            unlink($testFile);
            $this->line('   ✅ Storage writable: OK');
        } catch (\Exception $e) {
            $issues[] = "Storage not writable: " . $e->getMessage();
            $this->error('   ❌ Storage writable: FAILED');
        }
        
        // Check cache
        $this->info('🔍 Checking cache...');
        try {
            Cache::put('health_test', 'ok', 1);
            $value = Cache::get('health_test');
            Cache::forget('health_test');
            if ($value === 'ok') {
                $this->line('   ✅ Cache working: OK');
            } else {
                $issues[] = "Cache not working properly";
                $this->error('   ❌ Cache working: FAILED');
            }
        } catch (\Exception $e) {
            $issues[] = "Cache error: " . $e->getMessage();
            $this->error('   ❌ Cache working: FAILED');
        }
        
        // Check queue
        $this->info('🔍 Checking queue...');
        $queueConnection = config('queue.default');
        $this->line("   ✅ Queue driver: {$queueConnection}");
        
        // Check failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        if ($failedJobs > 0) {
            $issues[] = "{$failedJobs} failed jobs found in queue";
            $this->warn("   ⚠️ {$failedJobs} failed job(s) found");
        } else {
            $this->line('   ✅ No failed jobs');
        }
        
        // Check M-Pesa configuration
        $this->info('🔍 Checking M-Pesa configuration...');
        $mpesaConfigured = env('MPESA_CONSUMER_KEY') && env('MPESA_CONSUMER_SECRET');
        if ($mpesaConfigured) {
            $this->line('   ✅ M-Pesa credentials: Configured');
        } else {
            $issues[] = "M-Pesa credentials not configured";
            $this->warn('   ⚠️ M-Pesa credentials: NOT CONFIGURED');
        }
        
        // Check schedule
        $this->info('🔍 Checking scheduled tasks...');
        $lastBackup = $this->getLastBackupTime();
        if ($lastBackup) {
            $daysSinceBackup = $lastBackup->diffInDays(now());
            if ($daysSinceBackup > 7) {
                $issues[] = "No database backup in {$daysSinceBackup} days";
                $this->warn("   ⚠️ Last backup: {$lastBackup->diffForHumans()}");
            } else {
                $this->line("   ✅ Last backup: {$lastBackup->diffForHumans()}");
            }
        } else {
            $issues[] = "No database backup found";
            $this->warn('   ⚠️ No backup found');
        }
        
        // Summary
        $this->newLine();
        $this->info('=' . str_repeat('=', 50));
        
        if (empty($issues)) {
            $this->info('✅ SYSTEM HEALTH: GOOD - No issues found');
        } else {
            $this->error('⚠️ SYSTEM HEALTH: ISSUES FOUND');
            foreach ($issues as $issue) {
                $this->error("   • {$issue}");
            }
            
            if ($this->option('notify')) {
                $this->sendHealthAlert($issues);
            }
        }
        
        $this->info('=' . str_repeat('=', 50));
        
        return empty($issues) ? Command::SUCCESS : Command::FAILURE;
    }
    
    private function getLastBackupTime()
    {
        $backupDir = storage_path('app/backups');
        if (!file_exists($backupDir)) {
            return null;
        }
        
        $files = glob($backupDir . '/*.{sql,sql.gz}', GLOB_BRACE);
        if (empty($files)) {
            return null;
        }
        
        $latestFile = array_reduce($files, function($carry, $file) {
            $time = filemtime($file);
            return (!$carry || $time > $carry['time']) ? ['file' => $file, 'time' => $time] : $carry;
        });
        
        return \Carbon\Carbon::createFromTimestamp($latestFile['time']);
    }
    
    private function sendHealthAlert($issues)
    {
        // Send email to admin about health issues
        \Illuminate\Support\Facades\Mail::raw(
            "System Health Alert:\n\n" . implode("\n", $issues),
            function ($mail) {
                $mail->to(env('ADMIN_EMAIL', 'admin@chama.com'))
                    ->subject('Chama System Health Alert');
            }
        );
    }
}
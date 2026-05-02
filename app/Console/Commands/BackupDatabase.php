<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    protected $signature = 'chama:backup-database
                            {--compress : Compress the backup file}
                            {--only= : Backup only specific tables (comma separated)}';
    
    protected $description = 'Backup the entire database';

    public function handle()
    {
        $this->info('Starting database backup...');
        
        $backupDir = storage_path('app/backups');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $filename = 'backup_' . Carbon::now()->format('Y-m-d_H-i-s') . '.sql';
        $path = $backupDir . '/' . $filename;
        
        // Get database credentials
        $dbHost = env('DB_HOST', '127.0.0.1');
        $dbUser = env('DB_USERNAME', 'root');
        $dbPass = env('DB_PASSWORD', '');
        $dbName = env('DB_DATABASE', 'chama_db');
        
        // Build mysqldump command
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s',
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbHost),
            escapeshellarg($dbName)
        );
        
        // Add table filter if specified
        if ($this->option('only')) {
            $tables = explode(',', $this->option('only'));
            $command .= ' ' . implode(' ', array_map('escapeshellarg', $tables));
        }
        
        $command .= ' > ' . escapeshellarg($path);
        
        // Execute backup
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->error('Backup failed!');
            return Command::FAILURE;
        }
        
        $fileSize = filesize($path);
        $this->info("Backup created successfully: {$filename}");
        $this->info("File size: " . $this->formatBytes($fileSize));
        
        // Compress if requested
        if ($this->option('compress')) {
            $this->info('Compressing backup...');
            $gzipPath = $path . '.gz';
            file_put_contents($gzipPath, gzencode(file_get_contents($path)));
            unlink($path);
            $this->info("Compressed backup: {$filename}.gz");
            $this->info("Compressed size: " . $this->formatBytes(filesize($gzipPath)));
        }
        
        // Delete old backups (keep last 30 days)
        $this->cleanOldBackups($backupDir);
        
        $this->info('Backup completed successfully!');
        
        return Command::SUCCESS;
    }
    
    private function cleanOldBackups($backupDir)
    {
        $files = glob($backupDir . '/*.{sql,sql.gz}', GLOB_BRACE);
        $now = Carbon::now();
        $deletedCount = 0;
        
        foreach ($files as $file) {
            $fileTime = Carbon::createFromTimestamp(filemtime($file));
            if ($fileTime->diffInDays($now) > 30) {
                unlink($file);
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            $this->info("Deleted {$deletedCount} old backup(s) (older than 30 days)");
        }
    }
    
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}
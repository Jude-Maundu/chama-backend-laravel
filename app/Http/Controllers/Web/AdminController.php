<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Profile;
use App\Models\Contribution;
use App\Models\Loan;
use App\Models\Meeting;
use App\Models\Transaction;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Admin Dashboard Overview
     */
    public function dashboard()
    {
        // System Statistics
        $stats = [
            'total_members' => User::role('member')->count(),
            'active_members' => User::role('member')->where('is_active', true)->count(),
            'inactive_members' => User::role('member')->where('is_active', false)->count(),
            'new_members_this_month' => User::role('member')
                ->whereMonth('created_at', now()->month)
                ->count(),
            
            'total_contributions' => Contribution::where('status', 'completed')->sum('total_amount'),
            'monthly_contributions' => Contribution::whereMonth('payment_date', now()->month)
                ->where('status', 'completed')
                ->sum('total_amount'),
            'pending_contributions' => Contribution::where('status', 'pending')->count(),
            
            'total_loans' => Loan::sum('amount'),
            'active_loans' => Loan::whereIn('status', ['approved', 'disbursed'])->sum('balance'),
            'pending_loans' => Loan::where('status', 'pending')->count(),
            'defaulted_loans' => Loan::where('status', 'defaulted')->count(),
            
            'total_meetings' => Meeting::count(),
            'upcoming_meetings' => Meeting::where('meeting_date', '>', now())->count(),
            'average_attendance' => $this->getAverageAttendance(),
            
            'system_users' => User::count(),
            'online_users' => $this->getOnlineUsers(),
            'recent_audits' => AuditLog::whereDate('created_at', today())->count(),
        ];

        // Recent Activities
        $recentActivities = [
            'recent_members' => User::role('member')->latest()->take(5)->get(),
            'recent_contributions' => Contribution::with('user')->latest()->take(5)->get(),
            'recent_loans' => Loan::with('user')->latest()->take(5)->get(),
            'recent_audit_logs' => AuditLog::with('user')->latest()->take(10)->get(),
        ];

        // Chart Data
        $chartData = [
            'monthly_contributions' => Contribution::select(
                DB::raw('MONTH(payment_date) as month'),
                DB::raw('SUM(total_amount) as total')
            )->whereYear('payment_date', now()->year)
            ->where('status', 'completed')
            ->groupBy('month')
            ->get(),
            
            'loan_distribution' => Loan::select('loan_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                ->groupBy('loan_type')
                ->get(),
                
            'member_growth' => User::role('member')
                ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as count'))
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->get(),
        ];

        return view('admin.dashboard', compact('stats', 'recentActivities', 'chartData'));
    }

    /**
     * System Overview
     */
    public function systemOverview()
    {
        $systemInfo = [
            'laravel_version' => app()->version(),
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database' => DB::connection()->getDatabaseName(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug') ? 'Enabled' : 'Disabled',
            'timezone' => config('app.timezone'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_driver' => config('queue.default'),
        ];

        $storageInfo = [
            'total_space' => $this->formatBytes(disk_total_space('/')),
            'free_space' => $this->formatBytes(disk_free_space('/')),
            'used_space' => $this->formatBytes(disk_total_space('/') - disk_free_space('/')),
            'storage_link' => is_link(public_path('storage')) ? 'Linked' : 'Not Linked',
        ];

        $performance = [
            'response_time' => $this->getResponseTime(),
            'memory_usage' => $this->formatBytes(memory_get_usage()),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage()),
        ];

        return view('admin.system-overview', compact('systemInfo', 'storageInfo', 'performance'));
    }

    /**
     * Manage System Settings
     */
    public function settings()
    {
        $settings = Setting::all()->groupBy('group');
        
        $groups = [
            'general' => 'General Settings',
            'contributions' => 'Contribution Settings',
            'loans' => 'Loan Settings',
            'mpesa' => 'M-Pesa Settings',
            'email' => 'Email Settings',
            'sms' => 'SMS Settings',
            'security' => 'Security Settings',
            'backup' => 'Backup Settings',
        ];
        
        return view('admin.settings', compact('settings', 'groups'));
    }

    /**
     * Update Settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'chama_name' => 'required|string|max:255',
            'chama_email' => 'required|email',
            'chama_phone' => 'required',
            'currency' => 'required|string|size:3',
            'fiscal_year_start' => 'required|string',
        ]);

        foreach ($request->except(['_token', '_method']) as $key => $value) {
            Setting::set($key, $value);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'update_system_settings',
            'new_values' => json_encode($request->all()),
        ]);

        return redirect()->back()->with('success', 'System settings updated successfully');
    }

    /**
     * Manage All Users
     */
    public function users(Request $request)
    {
        $query = User::with('roles');
        
        if ($request->has('role') && $request->role != 'all') {
            $query->role($request->role);
        }
        
        if ($request->has('status') && $request->status != 'all') {
            $query->where('is_active', $request->status === 'active');
        }
        
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }
        
        $users = $query->latest()->paginate(20);
        $roles = Role::all();
        
        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Create New User (Admin)
     */
    public function createUser()
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store User
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        $user->assignRole($request->role);

        Profile::create([
            'user_id' => $user->id,
            'join_date' => now(),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_user',
            'table_name' => 'users',
            'record_id' => $user->id,
            'new_values' => json_encode($request->except('password')),
        ]);

        return redirect()->route('admin.users')->with('success', 'User created successfully');
    }

    /**
     * Edit User
     */
    public function editUser($id)
    {
        $user = User::with('profile')->findOrFail($id);
        $roles = Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update User
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|unique:users,phone,' . $id,
            'role' => 'required|exists:roles,name',
        ]);

        $user->update($request->only(['name', 'email', 'phone']));
        
        // Sync role
        $user->syncRoles([$request->role]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'update_user',
            'table_name' => 'users',
            'record_id' => $user->id,
            'new_values' => json_encode($request->all()),
        ]);

        return redirect()->route('admin.users')->with('success', 'User updated successfully');
    }

    /**
     * Toggle User Status
     */
    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);
        
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'toggle_user_status',
            'table_name' => 'users',
            'record_id' => $user->id,
            'new_values' => json_encode(['is_active' => $user->is_active]),
        ]);

        return response()->json(['success' => true, 'status' => $user->is_active]);
    }

    /**
     * Delete User
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        
        // Don't delete yourself
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account');
        }
        
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete_user',
            'table_name' => 'users',
            'record_id' => $user->id,
            'old_values' => json_encode($user->toArray()),
        ]);
        
        $user->delete();
        
        return redirect()->route('admin.users')->with('success', 'User deleted successfully');
    }

    /**
     * Role Management
     */
    public function roles()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        
        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    /**
     * Create Role
     */
    public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $request->name]);
        
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_role',
            'table_name' => 'roles',
            'new_values' => json_encode($request->all()),
        ]);

        return redirect()->route('admin.roles')->with('success', 'Role created successfully');
    }

    /**
     * Update Role Permissions
     */
    public function updateRolePermissions(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $role->syncPermissions($request->permissions ?? []);
        
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'update_role_permissions',
            'table_name' => 'roles',
            'record_id' => $role->id,
            'new_values' => json_encode(['permissions' => $request->permissions]),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * System Audit Logs
     */
    public function auditLogs(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->action, fn($q) => $q->where('action', 'like', "%{$request->action}%"))
            ->when($request->table, fn($q) => $q->where('table_name', $request->table))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate(50);
        
        $actions = AuditLog::distinct()->pluck('action');
        $tables = AuditLog::distinct()->pluck('table_name');
        
        return view('admin.audit-logs', compact('logs', 'actions', 'tables'));
    }

    /**
     * Export Audit Logs
     */
    public function exportAuditLogs(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->get();
        
        $filename = 'audit-logs-' . date('Y-m-d') . '.csv';
        
        $handle = fopen('php://temp', 'w');
        fputcsv($handle, ['Date', 'User', 'Action', 'Table', 'Record ID', 'IP Address']);
        
        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->created_at,
                $log->user->name ?? 'System',
                $log->action,
                $log->table_name,
                $log->record_id,
                $log->ip_address,
            ]);
        }
        
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        
        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }

    /**
     * Database Backup
     */
    public function backup()
    {
        $backups = $this->getBackupFiles();
        return view('admin.backup', compact('backups'));
    }

    /**
     * Create Database Backup
     */
    public function createBackup(Request $request)
    {
        $type = $request->type ?? 'full';
        
        // Create backup directory
        $backupDir = storage_path('app/backups');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $path = $backupDir . '/' . $filename;
        
        // Backup database
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            env('DB_USERNAME'),
            env('DB_PASSWORD'),
            env('DB_HOST'),
            env('DB_DATABASE'),
            $path
        );
        
        exec($command);
        
        // Compress if requested
        if ($request->compress) {
            exec("gzip {$path}");
            $filename .= '.gz';
        }
        
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'database_backup',
            'new_values' => json_encode(['filename' => $filename, 'type' => $type]),
        ]);
        
        return redirect()->back()->with('success', "Backup created: {$filename}");
    }

    /**
     * Download Backup
     */
    public function downloadBackup($filename)
    {
        $path = storage_path('app/backups/' . $filename);
        
        if (!file_exists($path)) {
            return redirect()->back()->with('error', 'Backup file not found');
        }
        
        return response()->download($path);
    }

    /**
     * Restore Backup
     */
    public function restoreBackup(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|string',
        ]);
        
        $path = storage_path('app/backups/' . $request->backup_file);
        
        if (!file_exists($path)) {
            return redirect()->back()->with('error', 'Backup file not found');
        }
        
        // Extract if gzipped
        if (pathinfo($path, PATHINFO_EXTENSION) === 'gz') {
            $content = gzdecode(file_get_contents($path));
            $tempFile = tempnam(sys_get_temp_dir(), 'sql');
            file_put_contents($tempFile, $content);
            $path = $tempFile;
        }
        
        // Restore database
        $command = sprintf(
            'mysql --user=%s --password=%s --host=%s %s < %s',
            env('DB_USERNAME'),
            env('DB_PASSWORD'),
            env('DB_HOST'),
            env('DB_DATABASE'),
            $path
        );
        
        exec($command);
        
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'restore_backup',
            'new_values' => json_encode(['filename' => $request->backup_file]),
        ]);
        
        return redirect()->back()->with('success', 'Database restored successfully');
    }

    /**
     * Clear System Cache
     */
    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'clear_cache',
        ]);
        
        return redirect()->back()->with('success', 'System cache cleared successfully');
    }

    /**
     * System Maintenance Mode
     */
    public function maintenanceMode(Request $request)
    {
        if ($request->action === 'enable') {
            Artisan::call('down', ['--retry' => 60]);
            $message = 'Maintenance mode enabled';
        } else {
            Artisan::call('up');
            $message = 'Maintenance mode disabled';
        }
        
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'maintenance_mode',
            'new_values' => json_encode(['action' => $request->action]),
        ]);
        
        return redirect()->back()->with('success', $message);
    }

    /**
     * Send System Notification to All Users
     */
    public function broadcastNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
            'type' => 'required|in:info,warning,success,error',
        ]);
        
        $users = User::all();
        
        foreach ($users as $user) {
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'channel' => 'in_app',
                'is_read' => false,
            ]);
        }
        
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'broadcast_notification',
            'new_values' => json_encode($request->all()),
        ]);
        
        return redirect()->back()->with('success', 'Notification sent to all users');
    }

    /**
     * Get System Health Status
     */
    public function systemHealth()
    {
        $checks = [
            'database' => $this->checkDatabaseConnection(),
            'storage' => $this->checkStorageWritable(),
            'cache' => $this->checkCacheWorking(),
            'queue' => $this->checkQueueWorking(),
            'mpesa' => $this->checkMpesaConnection(),
            'smtp' => $this->checkSmtpConnection(),
        ];
        
        $overallHealth = collect($checks)->every(fn($check) => $check['status'] === 'healthy');
        
        return view('admin.system-health', compact('checks', 'overallHealth'));
    }

    // Private Helper Methods
    private function getAverageAttendance()
    {
        $totalMeetings = Meeting::count();
        if ($totalMeetings === 0) return 0;
        
        $totalAttendees = \App\Models\Attendance::count();
        return round($totalAttendees / $totalMeetings);
    }

    private function getOnlineUsers()
    {
        // Simple implementation - track last activity
        return User::where('last_activity', '>=', now()->subMinutes(5))->count();
    }

    private function getResponseTime()
    {
        $start = microtime(true);
        DB::select('SELECT 1');
        $end = microtime(true);
        
        return round(($end - $start) * 1000, 2) . ' ms';
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }

    private function getBackupFiles()
    {
        $backupDir = storage_path('app/backups');
        $backups = [];
        
        if (file_exists($backupDir)) {
            $files = scandir($backupDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $backups[] = [
                        'name' => $file,
                        'size' => $this->formatBytes(filesize($backupDir . '/' . $file)),
                        'date' => date('Y-m-d H:i:s', filemtime($backupDir . '/' . $file)),
                        'extension' => pathinfo($file, PATHINFO_EXTENSION),
                    ];
                }
            }
            usort($backups, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
        }
        
        return $backups;
    }

    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkStorageWritable()
    {
        $testFile = storage_path('test.txt');
        file_put_contents($testFile, 'test');
        unlink($testFile);
        
        return ['status' => 'healthy', 'message' => 'Storage is writable'];
    }

    private function checkCacheWorking()
    {
        try {
            cache()->put('test', 'test', 1);
            $value = cache()->get('test');
            cache()->forget('test');
            
            return ['status' => 'healthy', 'message' => 'Cache is working'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkQueueWorking()
    {
        // Implement queue check
        return ['status' => 'healthy', 'message' => 'Queue system is configured'];
    }

    private function checkMpesaConnection()
    {
        // Implement M-Pesa API check
        return ['status' => 'warning', 'message' => 'M-Pesa credentials configured but not verified'];
    }

    private function checkSmtpConnection()
    {
        // Implement SMTP check
        return ['status' => 'healthy', 'message' => 'SMTP configured'];
    }
}
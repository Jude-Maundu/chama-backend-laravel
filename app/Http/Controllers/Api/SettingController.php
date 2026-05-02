<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Setting;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SettingController extends Controller
{
    public function index()
    {
        try {
            $settings = Setting::all()->mapWithKeys(function ($item) {
                return [$item->key => Setting::get($item->key)];
            })->toArray();
            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $setting = Setting::findOrFail($id);
            $setting->update($request->all());
            
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'update_settings',
                'new_values' => json_encode($request->all()),
            ]);
            
            return response()->json($setting);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $setting = Setting::findOrFail($id);
            return response()->json($setting);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Setting not found'], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $setting = Setting::create($request->all());
            
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'create_setting',
                'new_values' => json_encode($request->all()),
            ]);
            
            return response()->json($setting, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $setting = Setting::findOrFail($id);
            $setting->delete();
            
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'delete_setting',
                'new_values' => json_encode(['id' => $id]),
            ]);
            
            return response()->json(['message' => 'Setting deleted'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Setting not found'], 404);
        }
    }

    public function bulkUpdate(Request $request)
    {
        try {
            foreach ($request->all() as $key => $value) {
                Setting::set($key, $value);
            }
            
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'bulk_update_settings',
                'new_values' => json_encode($request->all()),
            ]);
            
            return response()->json(['message' => 'Settings updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Web interface methods below
    public function updateWeb(Request $request)
    {
        foreach ($request->except('_token') as $key => $value) {
            Setting::set($key, $value);
        }
        
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'update_settings',
            'new_values' => json_encode($request->all()),
        ]);
        
        return redirect()->back()->with('success', 'Settings updated successfully');
    }

    public function contributions()
    {
        $settings = [
            'monthly_contribution' => Setting::get('monthly_contribution', 5000),
            'late_penalty_percentage' => Setting::get('late_penalty_percentage', 5),
            'penalty_grace_days' => Setting::get('penalty_grace_days', 5),
            'contribution_due_day' => Setting::get('contribution_due_day', 5),
            'allow_partial_payments' => Setting::get('allow_partial_payments', false),
            'auto_reminder_days' => Setting::get('auto_reminder_days', 3),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function updateContributions(Request $request)
    {
        $request->validate([
            'monthly_contribution' => 'required|numeric|min:100',
            'late_penalty_percentage' => 'required|numeric|min:0|max:100',
            'penalty_grace_days' => 'required|integer|min:0',
            'contribution_due_day' => 'required|integer|min:1|max:28',
        ]);
        
        foreach ($request->all() as $key => $value) {
            Setting::set($key, $value);
        }
        
        return redirect()->back()->with('success', 'Contribution settings updated');
    }

    public function loans()
    {
        $settings = [
            'emergency_interest_rate' => Setting::get('emergency_interest_rate', 12),
            'development_interest_rate' => Setting::get('development_interest_rate', 10),
            'education_interest_rate' => Setting::get('education_interest_rate', 8),
            'welfare_interest_rate' => Setting::get('welfare_interest_rate', 5),
            'max_loan_ratio' => Setting::get('max_loan_ratio', 3),
            'min_loan_amount' => Setting::get('min_loan_amount', 1000),
            'max_loan_amount' => Setting::get('max_loan_amount', 500000),
            'loan_application_fee' => Setting::get('loan_application_fee', 0),
            'require_guarantors' => Setting::get('require_guarantors', true),
            'guarantor_count' => Setting::get('guarantor_count', 2),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function updateLoans(Request $request)
    {
        $request->validate([
            'max_loan_ratio' => 'required|numeric|min:1|max:10',
            'min_loan_amount' => 'required|numeric|min:100',
            'max_loan_amount' => 'required|numeric|min:1000',
        ]);
        
        foreach ($request->all() as $key => $value) {
            Setting::set($key, $value);
        }
        
        return redirect()->back()->with('success', 'Loan settings updated');
    }

    public function mpesa()
    {
        $settings = [
            'mpesa_shortcode' => config('mpesa.shortcode'),
            'mpesa_consumer_key' => config('mpesa.consumer_key'),
            'mpesa_environment' => config('mpesa.environment'),
            'mpesa_callback_url' => config('mpesa.callback_url'),
            'b2c_shortcode' => config('mpesa.b2c_shortcode'),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function updateMpesa(Request $request)
    {
        $request->validate([
            'mpesa_shortcode' => 'required|string',
            'mpesa_consumer_key' => 'required|string',
            'mpesa_environment' => 'required|in:sandbox,production',
        ]);
        
        // Update .env file programmatically
        $this->updateEnvFile($request->all());
        
        return redirect()->back()->with('success', 'M-Pesa settings updated. Restart queue worker for changes to take effect.');
    }

    public function roles()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        
        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $roles,
                'permissions' => $permissions
            ]
        ]);
    }

    public function updateRoles(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'array',
        ]);
        
        $role = Role::findById($request->role_id);
        $role->syncPermissions($request->permissions);
        
        return redirect()->back()->with('success', 'Role permissions updated');
    }

    public function backup()
    {
        $backups = [];
        
        // Get backup files from storage
        if (file_exists(storage_path('app/backups'))) {
            $files = scandir(storage_path('app/backups'));
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $backups[] = [
                        'name' => $file,
                        'size' => filesize(storage_path('app/backups/' . $file)),
                        'date' => date('Y-m-d H:i:s', filemtime(storage_path('app/backups/' . $file))),
                    ];
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => $backups
        ]);
    }

    public function createBackup(Request $request)
    {
        $type = $request->type ?? 'full';
        
        // Create backup directory
        if (!file_exists(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }
        
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $path = storage_path('app/backups/' . $filename);
        
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
        
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_backup',
            'new_values' => json_encode(['filename' => $filename]),
        ]);
        
        return redirect()->back()->with('success', 'Backup created successfully: ' . $filename);
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|string',
        ]);
        
        $path = storage_path('app/backups/' . $request->backup_file);
        
        if (!file_exists($path)) {
            return back()->with('error', 'Backup file not found');
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

    private function updateEnvFile($data)
    {
        $envFile = base_path('.env');
        $content = file_get_contents($envFile);
        
        foreach ($data as $key => $value) {
            $key = strtoupper($key);
            if (strpos($content, $key . '=') !== false) {
                $content = preg_replace('/' . $key . '=.*/', $key . '=' . $value, $content);
            } else {
                $content .= "\n" . $key . '=' . $value;
            }
        }
        
        file_put_contents($envFile, $content);
    }
}
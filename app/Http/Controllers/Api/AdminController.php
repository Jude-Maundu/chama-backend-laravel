<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chama;
use App\Models\User;
use App\Models\ChamaMember;
use App\Models\Contribution;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Get Global Platform Dashboard Statistics
     */
    public function dashboardStats()
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'total_chamas' => Chama::count(),
                'active_chamas' => Chama::where('status', 'active')->count(),
                'total_platform_contributions' => Contribution::where('status', 'completed')->sum('total_amount') ?? 0,
                'total_loans_disbursed' => Loan::where('status', 'disbursed')->sum('amount') ?? 0,
                'platform_revenue' => $this->calculatePlatformRevenue(),
                'system_health' => $this->getSystemHealth(),
            ];

            $recentUsers = User::latest()->take(8)->get([
                'id', 'name', 'email', 'created_at', 'is_active'
            ]);

            $recentChamas = Chama::withCount('members')
                ->latest()
                ->take(8)
                ->get([
                    'id', 'name', 'created_at', 'status'
                ]);

            $recentTransactions = Contribution::with(['user', 'chama'])
                ->where('status', 'completed')
                ->latest()
                ->take(10)
                ->get();

            return response()->json([
                'stats' => $stats,
                'recent_users' => $recentUsers,
                'recent_chamas' => $recentChamas,
                'recent_transactions' => $recentTransactions,
                'revenue_chart' => $this->getRevenueChartData(),
                'system_alerts' => $this->getSystemAlerts()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function calculatePlatformRevenue()
    {
        // Example: Platform takes 1% of all contributions as service fee
        $totalContributions = Contribution::where('status', 'completed')->sum('total_amount') ?? 0;
        return $totalContributions * 0.01;
    }

    private function getSystemHealth()
    {
        // Simple health check
        return 98; // Percentage
    }

    private function getRevenueChartData()
    {
        // Monthly revenue for the last 6 months
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $revenue = Contribution::where('status', 'completed')
                ->whereMonth('payment_date', $month->month)
                ->whereYear('payment_date', $month->year)
                ->sum('total_amount') * 0.01;
            
            $data[] = [
                'month' => $month->format('M'),
                'revenue' => $revenue
            ];
        }
        return $data;
    }

    private function getSystemAlerts()
    {
        return [
            ['id' => 1, 'type' => 'info', 'message' => 'System backup completed successfully', 'created_at' => now()->subHours(2)],
            ['id' => 2, 'type' => 'warning', 'message' => 'SMS gateway balance is below KES 500', 'created_at' => now()->subHours(5)],
        ];
    }

    /**
     * Get all users with pagination
     */
    public function getUsers(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);
            $search = $request->query('search', '');

            $query = User::query();

            if ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }

            $users = $query->paginate($perPage);

            return response()->json([
                'data' => $users->items(),
                'pagination' => [
                    'total' => $users->total(),
                    'page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'last_page' => $users->lastPage()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a new user
     */
    public function createUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'phone' => 'nullable|string|max:20|unique:users',
                'password' => 'required|min:8',
                'role' => 'required|in:member,chama-admin,super-admin',
                'is_active' => 'boolean'
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['is_active'] ?? true,
            ]);

            if ($validated['role'] === 'super-admin') {
                $user->assignRole('super-admin');
            } elseif ($validated['role'] === 'chama-admin') {
                $user->assignRole('chama-admin');
            } else {
                $user->assignRole('member');
            }

            return response()->json([
                'data' => $user,
                'message' => 'User created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update user details
     */
    public function updateUser(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $userId,
                'phone' => 'sometimes|nullable|string|max:20|unique:users,phone,' . $userId,
                'role' => 'sometimes|in:member,chama-admin,super-admin',
                'is_active' => 'sometimes|boolean'
            ]);

            $user->update($validated);

            if (isset($validated['role'])) {
                $user->syncRoles($validated['role']);
            }

            return response()->json([
                'data' => $user,
                'message' => 'User updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a user
     */
    public function deleteUser($userId)
    {
        try {
            $user = User::findOrFail($userId);
            
            // Prevent deleting the current user
            if ($user->id === Auth::id()) {
                return response()->json(['error' => 'Cannot delete your own account'], 403);
            }

            $user->delete();

            return response()->json(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all chamas with pagination
     */
    public function getChamas(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);
            $search = $request->query('search', '');

            $query = Chama::withCount('members');

            if ($search) {
                $query->where('name', 'like', "%{$search}%");
            }

            $chamas = $query->paginate($perPage);

            $data = $chamas->items();
            $formattedData = array_map(function ($chama) {
                return [
                    'id' => $chama->id,
                    'name' => $chama->name,
                    'description' => $chama->description ?? '',
                    'members_count' => $chama->members_count,
                    'created_at' => $chama->created_at,
                    'status' => $chama->status,
                    'is_active' => $chama->status === 'active'
                ];
            }, $data);

            return response()->json([
                'data' => $formattedData,
                'pagination' => [
                    'total' => $chamas->total(),
                    'page' => $chamas->currentPage(),
                    'per_page' => $chamas->perPage(),
                    'last_page' => $chamas->lastPage()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a new Chama
     */
    public function createChama(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:chamas|max:255',
                'description' => 'nullable|string',
                'admin_id' => 'required|exists:users,id',
            ]);

            $chama = Chama::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . Str::random(5),
                'description' => $validated['description'] ?? '',
                'created_by' => Auth::id(),
                'status' => 'active',
            ]);

            // Assign admin to chama
            ChamaMember::create([
                'chama_id' => $chama->id,
                'user_id' => $validated['admin_id'],
                'role' => 'admin',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            return response()->json([
                'data' => $chama,
                'message' => 'Chama created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update Chama details
     */
    public function updateChama(Request $request, $chamaId)
    {
        try {
            $chama = Chama::findOrFail($chamaId);

            $validated = $request->validate([
                'name' => 'sometimes|string|unique:chamas,name,' . $chamaId . '|max:255',
                'description' => 'sometimes|nullable|string',
                'status' => 'sometimes|in:active,inactive',
                'is_active' => 'sometimes|boolean',
            ]);

            // Convert is_active to status if provided
            if (isset($validated['is_active'])) {
                $validated['status'] = $validated['is_active'] ? 'active' : 'inactive';
                unset($validated['is_active']);
            }

            $chama->update($validated);

            return response()->json([
                'data' => $chama,
                'message' => 'Chama updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a Chama
     */
    public function deleteChama($chamaId)
    {
        try {
            $chama = Chama::findOrFail($chamaId);
            $chama->delete();

            return response()->json(['message' => 'Chama deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Calculate default rate for loans
     */
    private function calculateDefaultRate()
    {
        $totalLoans = Loan::where('status', 'approved')->count();
        if ($totalLoans === 0) return 0;

        $defaultedLoans = Loan::where('status', 'defaulted')->count();
        return round(($defaultedLoans / $totalLoans) * 100, 2);
    }

    /**
     * Get audit logs with pagination
     */
    public function getLogs(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $search = $request->query('search', '');
            $type = $request->query('type', '');
            $date = $request->query('date', '');

            $query = \App\Models\AuditLog::with('user');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                        ->orWhere('details', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($type) {
                $query->where('severity', $type);
            }

            if ($date) {
                $query->whereDate('created_at', $date);
            }

            $logs = $query->latest()->paginate($perPage);

            return response()->json([
                'data' => $logs->items(),
                'pagination' => [
                    'total' => $logs->total(),
                    'page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'last_page' => $logs->lastPage()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('AdminController.getLogs error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch logs', 'message' => $e->getMessage()], 500);
        }
    }
}

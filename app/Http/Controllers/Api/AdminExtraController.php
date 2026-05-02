<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chama;
use App\Models\Webhook;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminExtraController extends Controller
{
    /**
     * Custom Role Builder (Feature 50)
     */
    public function getRoles(Chama $chama)
    {
        // Spatie roles are global, but we can prefix them with chama_id or use team feature
        // For simplicity, we'll fetch roles
        $roles = Role::all();
        return response()->json(['success' => true, 'data' => $roles]);
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'required|array',
        ]);

        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return response()->json(['success' => true, 'data' => $role->load('permissions')], 201);
    }

    public function getPermissions()
    {
        $permissions = Permission::all();
        return response()->json(['success' => true, 'data' => $permissions]);
    }

    /**
     * Webhook Manager (Feature 54)
     */
    public function getWebhooks(Chama $chama)
    {
        $webhooks = Webhook::where('chama_id', $chama->id)->get();
        return response()->json(['success' => true, 'data' => $webhooks]);
    }

    public function storeWebhook(Request $request, Chama $chama)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'event_type' => 'required|string',
            'secret' => 'nullable|string',
        ]);

        $webhook = Webhook::create(array_merge($validated, [
            'chama_id' => $chama->id,
            'is_active' => true
        ]));

        return response()->json(['success' => true, 'data' => $webhook], 201);
    }

    public function deleteWebhook(Webhook $webhook)
    {
        $webhook->delete();
        return response()->json(['success' => true, 'message' => 'Webhook deleted']);
    }

    /**
     * Automated Backup Configuration (Feature 32)
     */
    public function getBackupSettings()
    {
        // Mock backup settings
        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => true,
                'frequency' => 'daily',
                'retention_days' => 30,
                'storage' => 'aws_s3',
                'last_backup' => now()->subHours(12)
            ]
        ]);
    }
}

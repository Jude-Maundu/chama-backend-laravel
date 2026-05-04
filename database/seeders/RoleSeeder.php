<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // Member Management
            'view_members', 'edit_members', 'delete_members', 'manage_member_roles',
            // Contributions
            'view_contributions', 'make_contributions', 'approve_contributions', 'manage_contribution_settings',
            // Loans
            'apply_loans', 'approve_loans', 'disburse_loans', 'manage_loan_settings', 'view_loans',
            // Reports
            'view_reports', 'export_reports', 'manage_financial_records',
            // Meetings
            'create_meetings', 'manage_meetings', 'view_meetings',
            // Settings
            'manage_settings', 'manage_system_settings', 'manage_chama_settings',
            // Dividends
            'view_dividends', 'calculate_dividends', 'distribute_dividends',
            // Audit
            'view_audit_logs', 'manage_backups'
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
        // SUPER ADMIN - System-wide administrator (has all permissions)
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdminRole->givePermissionTo(Permission::all());
        
        // ADMIN - Chama-specific administrator (manages specific chama members, contributions, etc.)
        $adminRole = Role::firstOrCreate(['name' => 'chama-admin']);
        $adminRole->givePermissionTo([
            'view_members', 'edit_members', 'manage_member_roles',
            'view_contributions', 'approve_contributions', 'manage_contribution_settings',
            'apply_loans', 'approve_loans', 'disburse_loans',
            'view_reports', 'export_reports', 'manage_financial_records',
            'create_meetings', 'manage_meetings', 'view_meetings',
            'manage_chama_settings', 'view_dividends', 'calculate_dividends',
            'view_audit_logs'
        ]);
        
        // TREASURER - Handles financial matters
        $treasurerRole = Role::firstOrCreate(['name' => 'treasurer']);
        $treasurerRole->givePermissionTo([
            'view_members', 'view_contributions', 'approve_contributions',
            'view_loans', 'approve_loans', 'view_reports', 'export_reports',
            'view_dividends', 'distribute_dividends', 'manage_financial_records',
            'view_audit_logs'
        ]);
        
        // MEMBER - Regular member
        $memberRole = Role::firstOrCreate(['name' => 'member']);
        $memberRole->givePermissionTo([
            'make_contributions', 'apply_loans', 'view_meetings', 'view_dividends'
        ]);
    }
}

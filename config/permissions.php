<?php
// config/permissions.php

return [
    'groups' => [
        'users' => [
            'view_users',
            'create_users', 
            'edit_users',
            'delete_users',
            'activate_users',
            'deactivate_users',
        ],
        'chamas' => [
            'view_chamas',
            'create_chamas',
            'edit_chamas',
            'delete_chamas',
            'manage_chama_settings',
        ],
        'members' => [
            'view_members',
            'add_members',
            'edit_members',
            'delete_members',
            'approve_members',
            'export_members',
        ],
        'contributions' => [
            'view_contributions',
            'make_contributions',
            'approve_contributions',
            'delete_contributions',
            'export_contributions',
        ],
        'loans' => [
            'view_loans',
            'apply_loans',
            'approve_loans',
            'disburse_loans',
            'manage_loan_settings',
        ],
        'meetings' => [
            'view_meetings',
            'schedule_meetings',
            'edit_meetings',
            'delete_meetings',
            'manage_attendance',
            'upload_minutes',
        ],
        'reports' => [
            'view_reports',
            'export_reports',
            'view_audit_logs',
        ],
        'finances' => [
            'view_finances',
            'manage_dividends',
            'manage_investments',
            'view_transactions',
        ],
        'system' => [
            'manage_settings',
            'manage_roles',
            'manage_backups',
            'view_system_health',
        ],
    ],
    
    'roles' => [
        'super-admin' => [
            'description' => 'Full system access across all Chamas',
            'permissions' => '*', // All permissions
        ],
        'chama-admin' => [
            'description' => 'Full access to a specific Chama',
            'permissions' => [
                'view_members', 'add_members', 'edit_members', 'delete_members', 'approve_members',
                'view_contributions', 'approve_contributions',
                'view_loans', 'approve_loans', 'disburse_loans',
                'view_meetings', 'schedule_meetings', 'edit_meetings', 'manage_attendance',
                'view_reports', 'export_reports',
                'view_finances', 'manage_dividends',
            ],
        ],
        'treasurer' => [
            'description' => 'Financial management for a Chama',
            'permissions' => [
                'view_members',
                'view_contributions', 'approve_contributions', 'export_contributions',
                'view_loans', 'approve_loans', 'disburse_loans',
                'view_reports', 'export_reports',
                'view_finances', 'manage_dividends', 'view_transactions',
            ],
        ],
        'secretary' => [
            'description' => 'Meeting and documentation management',
            'permissions' => [
                'view_members',
                'view_contributions',
                'view_loans',
                'view_meetings', 'schedule_meetings', 'edit_meetings', 'manage_attendance', 'upload_minutes',
                'view_reports',
            ],
        ],
        'member' => [
            'description' => 'Regular Chama member',
            'permissions' => [
                'view_own_profile',
                'make_contributions',
                'view_own_contributions',
                'apply_loans',
                'view_own_loans',
                'view_meetings',
                'view_reports',
            ],
        ],
    ],
];

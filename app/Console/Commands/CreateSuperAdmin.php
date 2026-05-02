<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateSuperAdmin extends Command
{
    protected $signature = 'app:create-super-admin';
    protected $description = 'Create a super-admin user for platform administration';

    public function handle()
    {
        $this->info('========================================');
        $this->info('  CHAMA PLATFORM - SUPER ADMIN SETUP');
        $this->info('========================================');
        $this->newLine();

        // Get inputs
        $name = $this->ask('Enter super-admin name');
        $email = $this->ask('Enter super-admin email');
        $phone = $this->ask('Enter super-admin phone number');
        $password = $this->secret('Enter super-admin password (min 8 characters)');
        
        // Validate
        if (strlen($password) < 8) {
            $this->error('❌ Password must be at least 8 characters long');
            return 1;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('❌ Email already exists in the system');
            return 1;
        }

        if (User::where('phone', $phone)->exists()) {
            $this->error('❌ Phone number already exists in the system');
            return 1;
        }

        // Create user
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make($password),
                'is_active' => true,
            ]);

            // Create profile
            Profile::create([
                'user_id' => $user->id,
                'national_id' => $this->ask('Enter national ID (optional)', ''),
                'join_date' => now(),
            ]);

            // Assign super-admin role
            $user->assignRole('super-admin');

            DB::commit();

            $this->newLine();
            $this->info('✅ Super Admin Created Successfully!');
            $this->newLine();
            $this->table(
                ['Field', 'Value'],
                [
                    ['Name', $name],
                    ['Email', $email],
                    ['Phone', $phone],
                    ['Role', 'Super Admin'],
                    ['Status', 'Active'],
                ]
            );
            $this->newLine();
            $this->info('You can now login to the admin panel with these credentials.');
            $this->newLine();

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error creating super admin: ' . $e->getMessage());
            return 1;
        }
    }
}

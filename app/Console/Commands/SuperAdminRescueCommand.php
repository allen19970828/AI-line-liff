<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class SuperAdminRescueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-super-admin {email : The email of the user to promote}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force promote a user to Super Admin (Rescue Mode)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} not found.");

            return 1;
        }

        // Ensure the role exists
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        if (! $user->hasRole('super_admin')) {
            $user->assignRole($role);
            $this->info("✅ User {$email} has been promoted to Super Admin.");
        } else {
            $this->warn("User {$email} is already a Super Admin.");
        }

        // Optional: Also clear school_id if it's a maintenance account and they want to see everything
        if ($this->confirm('Do you want to clear school_id for this account to enable global view?', false)) {
            $user->school_id = null;
            $user->save();
            $this->info("School ID cleared for {$email}.");
        }

        return 0;
    }
}

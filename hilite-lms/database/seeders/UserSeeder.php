<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // === HiLITE Builders (company_id = 1) ===

        // Admin
        User::create([
            'company_id' => 1, 'name' => 'Admin Ravi', 'email' => 'admin@hilitebuilders.com',
            'password' => Hash::make('password123'), 'role' => 'admin',
            'branch_id' => 1, 'team_id' => null,
        ]);

        // Manager
        User::create([
            'company_id' => 1, 'name' => 'Manager Deepa', 'email' => 'manager@hilitebuilders.com',
            'password' => Hash::make('password123'), 'role' => 'manager',
            'branch_id' => 1, 'team_id' => null,
        ]);

        // Branch Head
        User::create([
            'company_id' => 1, 'name' => 'BH Anil', 'email' => 'bh@hilitebuilders.com',
            'password' => Hash::make('password123'), 'role' => 'branch_head',
            'branch_id' => 1, 'team_id' => null,
        ]);

        // Team Lead - Team Alpha
        User::create([
            'company_id' => 1, 'name' => 'TL Suresh', 'email' => 'tl.suresh@hilitebuilders.com',
            'password' => Hash::make('password123'), 'role' => 'team_lead',
            'branch_id' => 1, 'team_id' => 1,
        ]);

        // Salespersons - Team Alpha
        User::create([
            'company_id' => 1, 'name' => 'Priya S', 'email' => 'priya@hilitebuilders.com',
            'password' => Hash::make('password123'), 'role' => 'salesperson',
            'branch_id' => 1, 'team_id' => 1,
        ]);

        User::create([
            'company_id' => 1, 'name' => 'Kiran M', 'email' => 'kiran@hilitebuilders.com',
            'password' => Hash::make('password123'), 'role' => 'salesperson',
            'branch_id' => 1, 'team_id' => 1,
        ]);

        User::create([
            'company_id' => 1, 'name' => 'Ajay R', 'email' => 'ajay@hilitebuilders.com',
            'password' => Hash::make('password123'), 'role' => 'salesperson',
            'branch_id' => 1, 'team_id' => 1,
        ]);

        // Team Lead - Team Beta
        User::create([
            'company_id' => 1, 'name' => 'TL Meera', 'email' => 'tl.meera@hilitebuilders.com',
            'password' => Hash::make('password123'), 'role' => 'team_lead',
            'branch_id' => 1, 'team_id' => 2,
        ]);

        // Salespersons - Team Beta
        User::create([
            'company_id' => 1, 'name' => 'Rahul V', 'email' => 'rahul@hilitebuilders.com',
            'password' => Hash::make('password123'), 'role' => 'salesperson',
            'branch_id' => 1, 'team_id' => 2,
        ]);

        User::create([
            'company_id' => 1, 'name' => 'Sneha K', 'email' => 'sneha@hilitebuilders.com',
            'password' => Hash::make('password123'), 'role' => 'salesperson',
            'branch_id' => 1, 'team_id' => 2,
        ]);

        // System user for webhook audit attribution
        User::create([
            'company_id' => 1, 'name' => 'System', 'email' => 'system+hilite-builders@internal.local',
            'password' => Hash::make(\Illuminate\Support\Str::random(64)), 'role' => 'salesperson',
            'branch_id' => null, 'team_id' => null, 'is_active' => false,
        ]);

        // === HiLITE Properties (company_id = 2) ===

        // Admin
        User::create([
            'company_id' => 2, 'name' => 'Admin Lakshmi', 'email' => 'admin@hiliteproperties.com',
            'password' => Hash::make('password123'), 'role' => 'admin',
            'branch_id' => 3, 'team_id' => null,
        ]);

        // Manager
        User::create([
            'company_id' => 2, 'name' => 'Manager Vivek', 'email' => 'manager@hiliteproperties.com',
            'password' => Hash::make('password123'), 'role' => 'manager',
            'branch_id' => 3, 'team_id' => null,
        ]);

        // Team Lead
        User::create([
            'company_id' => 2, 'name' => 'TL Anjali', 'email' => 'tl.anjali@hiliteproperties.com',
            'password' => Hash::make('password123'), 'role' => 'team_lead',
            'branch_id' => 3, 'team_id' => 4,
        ]);

        // Salespersons
        User::create([
            'company_id' => 2, 'name' => 'Nisha T', 'email' => 'nisha@hiliteproperties.com',
            'password' => Hash::make('password123'), 'role' => 'salesperson',
            'branch_id' => 3, 'team_id' => 4,
        ]);

        User::create([
            'company_id' => 2, 'name' => 'Vinod P', 'email' => 'vinod@hiliteproperties.com',
            'password' => Hash::make('password123'), 'role' => 'salesperson',
            'branch_id' => 3, 'team_id' => 4,
        ]);

        // System user for webhook
        User::create([
            'company_id' => 2, 'name' => 'System', 'email' => 'system+hilite-properties@internal.local',
            'password' => Hash::make(\Illuminate\Support\Str::random(64)), 'role' => 'salesperson',
            'branch_id' => null, 'team_id' => null, 'is_active' => false,
        ]);
    }
}

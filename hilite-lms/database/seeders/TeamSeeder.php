<?php
namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        // HiLITE Builders teams
        Team::create(['company_id' => 1, 'name' => 'Sales Team Alpha', 'branch_id' => 1]);
        Team::create(['company_id' => 1, 'name' => 'Sales Team Beta', 'branch_id' => 1]);
        Team::create(['company_id' => 1, 'name' => 'Calicut Sales', 'branch_id' => 2]);

        // HiLITE Properties teams
        Team::create(['company_id' => 2, 'name' => 'Properties Team A', 'branch_id' => 3]);
    }
}

<?php
namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        // HiLITE Builders branches
        Branch::create(['company_id' => 1, 'name' => 'Kochi HQ']);
        Branch::create(['company_id' => 1, 'name' => 'Calicut Branch']);

        // HiLITE Properties branches
        Branch::create(['company_id' => 2, 'name' => 'Trivandrum HQ']);
    }
}

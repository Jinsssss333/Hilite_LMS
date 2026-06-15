<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            BranchSeeder::class,
            TeamSeeder::class,
            UserSeeder::class,
            PipelineStageSeeder::class,
            DispositionSeeder::class,
            LeadSeeder::class,
        ]);
    }
}

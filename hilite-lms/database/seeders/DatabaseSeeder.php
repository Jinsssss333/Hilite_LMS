<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeder execution order matters — foreign keys must be satisfied.
     * Dev C owns this file and is responsible for correct ordering.
     *
     * Order:
     *   1. CompanySeeder     — master companies (Dev A)
     *   2. BranchSeeder      — branches per company (Dev A)
     *   3. TeamSeeder        — teams per branch (Dev A)
     *   4. UserSeeder        — users incl. system users (Dev A + Dev C spec)
     *   5. PipelineStageSeeder — stages per company (Dev A)
     *   6. DispositionSeeder — dispositions per company (Dev A)
     *   7. SlaPolicySeeder   — SLA policies per stage/company (Dev C)
     *   8. LeadSeeder        — sample leads + engagements (Dev A)
     */
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            BranchSeeder::class,
            TeamSeeder::class,
            UserSeeder::class,
            PipelineStageSeeder::class,
            DispositionSeeder::class,
            SlaPolicySeeder::class,
            LeadSeeder::class,
        ]);
    }
}

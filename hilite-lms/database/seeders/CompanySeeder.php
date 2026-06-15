<?php
namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::create([
            'name' => 'HiLITE Builders',
            'slug' => 'hilite-builders',
            'webhook_key' => 'whk_hilite_builders_secret',
            'is_active' => true,
        ]);

        Company::create([
            'name' => 'HiLITE Properties',
            'slug' => 'hilite-properties',
            'webhook_key' => 'whk_hilite_properties_secret',
            'is_active' => true,
        ]);
    }
}

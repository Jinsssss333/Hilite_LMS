<?php
namespace Database\Seeders;

use App\Models\PipelineStage;
use Illuminate\Database\Seeder;

class PipelineStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['name' => 'New',                    'order' => 1, 'color' => '#6366f1', 'sla_days' => 2,    'is_closed' => false],
            ['name' => 'Contacted',              'order' => 2, 'color' => '#f59e0b', 'sla_days' => 5,    'is_closed' => false],
            ['name' => 'Interested',             'order' => 3, 'color' => '#10b981', 'sla_days' => 7,    'is_closed' => false],
            ['name' => 'Site Visit Scheduled',   'order' => 4, 'color' => '#3b82f6', 'sla_days' => 3,    'is_closed' => false],
            ['name' => 'Negotiation',            'order' => 5, 'color' => '#8b5cf6', 'sla_days' => 10,   'is_closed' => false],
            ['name' => 'Booked',                 'order' => 6, 'color' => '#22c55e', 'sla_days' => null,  'is_closed' => true],
            ['name' => 'Lost',                   'order' => 7, 'color' => '#ef4444', 'sla_days' => null,  'is_closed' => true],
            ['name' => 'Not Interested',         'order' => 8, 'color' => '#6b7280', 'sla_days' => null,  'is_closed' => true],
        ];

        // Create for both companies
        foreach ([1, 2] as $companyId) {
            foreach ($stages as $stage) {
                PipelineStage::create(array_merge($stage, ['company_id' => $companyId]));
            }
        }
    }
}

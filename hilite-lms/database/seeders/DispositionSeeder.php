<?php
namespace Database\Seeders;

use App\Models\Disposition;
use App\Models\PipelineStage;
use Illuminate\Database\Seeder;

class DispositionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([1, 2] as $companyId) {
            $stages = PipelineStage::where('company_id', $companyId)->pluck('id', 'name');

            $dispositions = [
                ['label' => 'No Answer',              'stage_id' => null],
                ['label' => 'Callback Requested',     'stage_id' => null],
                ['label' => 'Interested',             'stage_id' => $stages['Interested'] ?? null],
                ['label' => 'Not Interested',         'stage_id' => null],
                ['label' => 'Site Visit Confirmed',   'stage_id' => $stages['Site Visit Scheduled'] ?? null],
                ['label' => 'Price Negotiation',      'stage_id' => $stages['Negotiation'] ?? null],
                ['label' => 'Deal Closed',            'stage_id' => $stages['Booked'] ?? null],
                ['label' => 'Lost — Budget',          'stage_id' => $stages['Lost'] ?? null],
                ['label' => 'Lost — Competitor',      'stage_id' => $stages['Lost'] ?? null],
            ];

            foreach ($dispositions as $d) {
                Disposition::create(array_merge($d, ['company_id' => $companyId]));
            }
        }
    }
}

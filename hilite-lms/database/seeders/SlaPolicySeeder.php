<?php

namespace Database\Seeders;

use App\Models\PipelineStage;
use App\Models\SlaPolicy;
use Illuminate\Database\Seeder;

class SlaPolicySeeder extends Seeder
{
    /**
     * Default SLA policies (days) per pipeline stage, seeded for every company.
     * Matches the API_CONTRACTS.md defaults exactly.
     */
    private array $defaults = [
        'New'                  => ['sla_days' => 2,  'escalate_to_role' => 'team_lead'],
        'Contacted'            => ['sla_days' => 5,  'escalate_to_role' => 'team_lead'],
        'Interested'           => ['sla_days' => 7,  'escalate_to_role' => 'manager'],
        'Site Visit Scheduled' => ['sla_days' => 3,  'escalate_to_role' => 'team_lead'],
        'Negotiation'          => ['sla_days' => 10, 'escalate_to_role' => 'manager'],
        // Closed stages have no SLA
        'Booked'         => null,
        'Lost'           => null,
        'Not Interested' => null,
    ];

    public function run(): void
    {
        foreach ([1, 2] as $companyId) {
            $stages = PipelineStage::where('company_id', $companyId)->get()->keyBy('name');

            foreach ($this->defaults as $stageName => $policy) {
                if (!isset($stages[$stageName]) || $policy === null) {
                    continue;
                }

                SlaPolicy::firstOrCreate(
                    ['company_id' => $companyId, 'stage_id' => $stages[$stageName]->id],
                    [
                        'sla_days'         => $policy['sla_days'],
                        'escalate_to_role' => $policy['escalate_to_role'],
                    ]
                );
            }
        }
    }
}

<?php
namespace Database\Seeders;

use App\Models\Lead;
use App\Models\LeadEngagement;
use App\Models\PipelineStage;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $sampleLeads = [
            ['name' => 'Arjun Nair',       'phone_e164' => '+919876543210', 'email' => 'arjun@email.com'],
            ['name' => 'Meena Krishnan',   'phone_e164' => '+919876543211', 'email' => 'meena@email.com'],
            ['name' => 'Suresh Kumar',     'phone_e164' => '+919876543212', 'email' => 'suresh.k@email.com'],
            ['name' => 'Lakshmi Devi',     'phone_e164' => '+919876543213', 'email' => 'lakshmi@email.com'],
            ['name' => 'Rajesh Menon',     'phone_e164' => '+919876543214', 'email' => 'rajesh.m@email.com'],
            ['name' => 'Priya Varma',      'phone_e164' => '+919876543215', 'email' => null],
            ['name' => 'Anil George',      'phone_e164' => '+919876543216', 'email' => 'anil.g@email.com'],
            ['name' => 'Divya Pillai',     'phone_e164' => '+919876543217', 'email' => 'divya@email.com'],
            ['name' => 'Mohammed Ashraf',  'phone_e164' => '+919876543218', 'email' => 'ashraf@email.com'],
            ['name' => 'Sneha Thomas',     'phone_e164' => '+919876543219', 'email' => null],
            ['name' => 'Vijay Kumar',      'phone_e164' => '+919876543220', 'email' => 'vijay@email.com'],
            ['name' => 'Asha Nair',        'phone_e164' => '+919876543221', 'email' => 'asha@email.com'],
            ['name' => 'Ramesh Babu',      'phone_e164' => '+919876543222', 'email' => null],
            ['name' => 'Fatima Beevi',     'phone_e164' => '+919876543223', 'email' => 'fatima@email.com'],
            ['name' => 'Joseph Mathew',    'phone_e164' => '+919876543224', 'email' => 'joseph@email.com'],
            ['name' => 'Ahmed Khan',       'phone_e164' => '+971501234567', 'email' => 'ahmed@email.com'],
            ['name' => 'Sara Ali',         'phone_e164' => '+971502345678', 'email' => 'sara@email.com'],
            ['name' => 'Deepak Sharma',    'phone_e164' => '+919876543225', 'email' => 'deepak@email.com'],
            ['name' => 'Kavitha Rajan',    'phone_e164' => '+919876543226', 'email' => null],
            ['name' => 'Sanjay Gupta',     'phone_e164' => '+919876543227', 'email' => 'sanjay@email.com'],
        ];

        $company1Stages = PipelineStage::where('company_id', 1)->pluck('id', 'name');
        $company2Stages = PipelineStage::where('company_id', 2)->pluck('id', 'name');

        // User IDs: Company 1 salespersons are 5,6,7 (Priya, Kiran, Ajay)
        // Company 2 salespersons are 15,16 (Nisha, Vinod)
        $stageNames = ['New', 'Contacted', 'Interested', 'Site Visit Scheduled', 'Negotiation'];
        $sources = ['manual', 'csv', 'webhook'];

        foreach ($sampleLeads as $i => $leadData) {
            $lead = Lead::create(array_merge($leadData, ['status' => 'active']));

            // First 14 leads belong to Company 1
            if ($i < 14) {
                $stageName = $stageNames[$i % count($stageNames)];
                $assignedUserId = null;
                if ($i < 10) {
                    // Assign to salespersons in round-robin: 5, 6, 7
                    $assignedUserId = [5, 6, 7][$i % 3];
                }
                // Leave some unassigned

                LeadEngagement::withoutGlobalScopes()->create([
                    'company_id'       => 1,
                    'lead_id'          => $lead->id,
                    'assigned_user_id' => $assignedUserId,
                    'stage_id'         => $company1Stages[$stageName],
                    'source'           => $sources[$i % count($sources)],
                    'status'           => 'active',
                    'last_activity_at' => now()->subDays(rand(0, 10)),
                    'sla_due_at'       => now()->addDays(rand(1, 7)),
                    'sla_breached'     => $i % 5 === 0,
                ]);
            }

            // Last 6 leads also belong to Company 2 (some shared leads)
            if ($i >= 14) {
                $stageName = $stageNames[$i % count($stageNames)];
                $assignedUserId = [15, 16, null][$i % 3];

                LeadEngagement::withoutGlobalScopes()->create([
                    'company_id'       => 2,
                    'lead_id'          => $lead->id,
                    'assigned_user_id' => $assignedUserId,
                    'stage_id'         => $company2Stages[$stageName],
                    'source'           => $sources[$i % count($sources)],
                    'status'           => 'active',
                    'last_activity_at' => now()->subDays(rand(0, 5)),
                    'sla_due_at'       => now()->addDays(rand(1, 5)),
                    'sla_breached'     => false,
                ]);
            }
        }
    }
}

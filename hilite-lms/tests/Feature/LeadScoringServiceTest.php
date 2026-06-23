<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Lead;
use App\Models\LeadEngagement;
use App\Models\Company;
use App\Models\PipelineStage;
use App\Services\LeadScoringService;
use App\Services\AuditLogService;

class LeadScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LeadScoringService $scoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scoringService = app(LeadScoringService::class);
    }

    private function setupCompanyAndStage()
    {
        $company = Company::create(['name' => 'Test', 'slug' => uniqid()]);
        $stage = PipelineStage::create(['company_id' => $company->id, 'name' => 'New']);
        return [$company, $stage];
    }

    public function test_calculate_applies_profile_scores()
    {
        [$company, $stage] = $this->setupCompanyAndStage();
        
        $lead = Lead::create([
            'email' => 'test@example.com', // +10
            'phone_e164' => '+1234567890', // +10
            'name' => 'Test Lead',
            'meta' => [
                'company' => 'Acme Corp', // +10
                'budget' => '10000',      // +20
                'is_decision_maker' => true // +20
            ]
        ]); // Profile score = 70

        $engagement = LeadEngagement::create([
            'company_id' => $company->id,
            'lead_id' => $lead->id,
            'stage_id' => $stage->id,
            'source' => 'csv', // +0
        ]);
        
        // Force created_at to be exactly now so freshness = 10
        $engagement->created_at = now();
        $engagement->saveQuietly(); // save without triggering observer

        $this->scoringService->calculate($engagement);

        // 70 (Profile) + 10 (Freshness) = 80
        $this->assertEquals(80, $engagement->fresh()->lead_score);
        $this->assertEquals('Very Hot', $engagement->fresh()->lead_rating);
    }

    public function test_calculate_applies_source_scores()
    {
        [$company, $stage] = $this->setupCompanyAndStage();
        $lead = Lead::create(['name' => 'T', 'phone_e164' => '+111']); // Phone (+10)
        
        $engagement = LeadEngagement::create([
            'company_id' => $company->id,
            'lead_id' => $lead->id,
            'stage_id' => $stage->id,
            'source' => 'webhook', // +20
        ]);
        
        $engagement->created_at = now();
        $engagement->saveQuietly();

        $this->scoringService->calculate($engagement);
        
        // 10 (Profile) + 20 (Source) + 10 (Freshness) = 40
        $this->assertEquals(40, $engagement->fresh()->lead_score);
    }

    public function test_calculate_applies_freshness_and_negatives()
    {
        [$company, $stage] = $this->setupCompanyAndStage();
        $lead = Lead::create(['name' => 'T', 'phone_e164' => '+222', 'meta' => ['email_bounced' => true]]); // Phone (+10), Bounced (-20)
        
        $engagement = LeadEngagement::create([
            'company_id' => $company->id,
            'lead_id' => $lead->id,
            'stage_id' => $stage->id,
            'source' => 'csv', // +0
        ]);
        
        // Make it 40 days old so freshness is -10 and inactivity is -15
        $engagement->created_at = now()->subDays(40);
        $engagement->last_activity_at = now()->subDays(40);
        $engagement->saveQuietly();

        $this->scoringService->calculate($engagement);
        
        // 10 (Profile) - 20 (Bounced) + 0 (Source) - 10 (Freshness) - 15 (Inactive) = -35 -> Clamped to 0
        $this->assertEquals(0, $engagement->fresh()->lead_score);
    }

    public function test_observer_triggers_recalculation()
    {
        [$company, $stage] = $this->setupCompanyAndStage();
        $lead = Lead::create(['name' => 'T', 'phone_e164' => '+333']); // Phone (+10)
        $user = \App\Models\User::create([
            'name' => 'Agent',
            'email' => 'agent' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'role' => 'salesperson'
        ]);
        
        $engagement = LeadEngagement::create([
            'company_id' => $company->id,
            'lead_id' => $lead->id,
            'stage_id' => $stage->id,
            'source' => 'csv', // +0
        ]);

        $engagement->created_at = now();
        $engagement->saveQuietly();

        // Trigger observer manually by modifying assigned_user_id
        $engagement->assigned_user_id = $user->id;
        $engagement->save();

        // 10 (Profile) + 0 (Source) + 10 (Freshness) = 20
        $this->assertEquals(20, $engagement->fresh()->lead_score);

        // Update lead to add email (+10)
        $lead->update(['email' => 'test@test.com']); // LeadObserver triggers calculate

        // 20 + 10 = 30
        $this->assertEquals(30, $engagement->fresh()->lead_score);
    }
}

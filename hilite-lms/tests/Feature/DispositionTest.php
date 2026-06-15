<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Team;
use App\Models\Disposition;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispositionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Company $otherCompany;
    protected User $user;
    protected PipelineStage $interestedStage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'is_active' => true,
        ]);

        $this->otherCompany = Company::create([
            'name' => 'Other Company',
            'slug' => 'other-company',
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
        ]);

        $team = Team::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Alpha Team',
        ]);

        $this->user = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'team_id' => $team->id,
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'role' => 'salesperson',
        ]);

        $this->interestedStage = PipelineStage::create([
            'company_id' => $this->company->id,
            'name' => 'Interested',
            'order' => 3,
            'color' => '#10b981',
            'sla_days' => 7,
            'is_closed' => false,
        ]);

        // Create dispositions for our company
        Disposition::create(['company_id' => $this->company->id, 'stage_id' => null, 'label' => 'No Answer']);
        Disposition::create(['company_id' => $this->company->id, 'stage_id' => null, 'label' => 'Callback Requested']);
        Disposition::create(['company_id' => $this->company->id, 'stage_id' => $this->interestedStage->id, 'label' => 'Interested']);
        Disposition::create(['company_id' => $this->company->id, 'stage_id' => null, 'label' => 'Not Interested']);

        // Create a disposition for the other company (should NOT appear)
        $otherStage = PipelineStage::create([
            'company_id' => $this->otherCompany->id,
            'name' => 'Interested',
            'order' => 3,
            'color' => '#10b981',
            'sla_days' => 7,
            'is_closed' => false,
        ]);
        Disposition::create(['company_id' => $this->otherCompany->id, 'stage_id' => $otherStage->id, 'label' => 'Other Company Disp']);

        app()->instance('current_company_id', $this->company->id);
    }

    public function test_returns_only_current_companys_dispositions(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/dispositions');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(4, $data);

        // Ensure no disposition from other company
        $labels = collect($data)->pluck('label')->toArray();
        $this->assertNotContains('Other Company Disp', $labels);
    }

    public function test_stage_id_filter_returns_stage_specific_and_global_dispositions(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/dispositions?stage_id={$this->interestedStage->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $data = $response->json('data');

        // Should include: 'Interested' (stage-specific) + 'No Answer', 'Callback Requested', 'Not Interested' (global)
        $this->assertCount(4, $data);

        $labels = collect($data)->pluck('label')->toArray();
        $this->assertContains('Interested', $labels);
        $this->assertContains('No Answer', $labels);
        $this->assertContains('Callback Requested', $labels);
        $this->assertContains('Not Interested', $labels);
    }

    public function test_stage_id_filter_excludes_other_stage_specific_dispositions(): void
    {
        // Create a disposition specific to a different stage
        $otherStage = PipelineStage::create([
            'company_id' => $this->company->id,
            'name' => 'Negotiation',
            'order' => 5,
            'color' => '#8b5cf6',
            'sla_days' => 10,
            'is_closed' => false,
        ]);

        Disposition::create([
            'company_id' => $this->company->id,
            'stage_id' => $otherStage->id,
            'label' => 'Price Negotiation',
        ]);

        // Filter by Interested stage — should NOT include 'Price Negotiation'
        $response = $this->actingAs($this->user)
            ->getJson("/api/dispositions?stage_id={$this->interestedStage->id}");

        $labels = collect($response->json('data'))->pluck('label')->toArray();
        $this->assertNotContains('Price Negotiation', $labels);
    }

    public function test_dispositions_are_ordered_alphabetically(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/dispositions');

        $labels = collect($response->json('data'))->pluck('label')->toArray();

        $sorted = $labels;
        sort($sorted);
        $this->assertEquals($sorted, $labels);
    }

    public function test_disposition_response_shape_matches_contract(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/dispositions');

        $first = $response->json('data.0');
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('label', $first);
        $this->assertArrayHasKey('stage_id', $first);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Team;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineStageTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Company $otherCompany;
    protected User $user;

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

        // Create stages for our company
        PipelineStage::create(['company_id' => $this->company->id, 'name' => 'New', 'order' => 1, 'color' => '#6366f1', 'sla_days' => 2, 'is_closed' => false]);
        PipelineStage::create(['company_id' => $this->company->id, 'name' => 'Contacted', 'order' => 2, 'color' => '#f59e0b', 'sla_days' => 5, 'is_closed' => false]);
        PipelineStage::create(['company_id' => $this->company->id, 'name' => 'Booked', 'order' => 6, 'color' => '#22c55e', 'sla_days' => null, 'is_closed' => true]);

        // Create stages for other company (should not appear)
        PipelineStage::create(['company_id' => $this->otherCompany->id, 'name' => 'Other New', 'order' => 1, 'color' => '#6366f1', 'sla_days' => 2, 'is_closed' => false]);

        app()->instance('current_company_id', $this->company->id);
    }

    public function test_returns_only_current_companys_stages(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/pipeline-stages');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(3, $data);

        $names = collect($data)->pluck('name')->toArray();
        $this->assertNotContains('Other New', $names);
    }

    public function test_stages_are_ordered_by_order_column(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/pipeline-stages');

        $data = $response->json('data');
        $orders = collect($data)->pluck('order')->toArray();

        $this->assertEquals([1, 2, 6], $orders);
    }

    public function test_response_shape_matches_api_contract(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/pipeline-stages');

        $first = $response->json('data.0');
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('order', $first);
        $this->assertArrayHasKey('color', $first);
        $this->assertArrayHasKey('sla_days', $first);
        $this->assertArrayHasKey('is_closed', $first);
    }

    public function test_closed_stage_has_is_closed_true(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/pipeline-stages');

        $data = collect($response->json('data'));
        $booked = $data->firstWhere('name', 'Booked');

        $this->assertTrue($booked['is_closed']);
        $this->assertNull($booked['sla_days']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/pipeline-stages');
        $response->assertStatus(401);
    }
}

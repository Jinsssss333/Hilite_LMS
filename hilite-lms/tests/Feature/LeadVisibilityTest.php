<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\LeadEngagement;

class LeadVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFreshDatabase();
    }

    public function test_salesperson_only_sees_own_or_unassigned_leads_in_index()
    {
        $salesperson = User::where('company_id', 1)->where('role', 'salesperson')->first();
        
        $response = $this->actingAs($salesperson)->getJson('/api/leads');
        $response->assertStatus(200);

        foreach ($response->json('data') as $item) {
            $eng = LeadEngagement::find($item['engagement_id']);
            $this->assertTrue($eng->assigned_user_id === null || $eng->assigned_user_id === $salesperson->id);
        }
    }

    public function test_salesperson_cannot_view_colleagues_lead()
    {
        $salesperson = User::where('company_id', 1)->where('role', 'salesperson')->first();
        $colleague = User::where('company_id', 1)->where('role', 'salesperson')->where('id', '!=', $salesperson->id)->first();
        
        $engagement = LeadEngagement::where('company_id', 1)->where('assigned_user_id', $colleague->id)->first();
        $this->assertNotNull($engagement);

        $response = $this->actingAs($salesperson)->getJson('/api/leads/' . $engagement->id);
        $response->assertStatus(403);
    }

    public function test_salesperson_can_view_unassigned_lead()
    {
        $salesperson = User::where('company_id', 1)->where('role', 'salesperson')->first();
        $engagement = LeadEngagement::where('company_id', 1)->whereNull('assigned_user_id')->first();
        $this->assertNotNull($engagement);

        $response = $this->actingAs($salesperson)->getJson('/api/leads/' . $engagement->id);
        $response->assertStatus(200);
    }

    public function test_salesperson_can_view_own_lead()
    {
        $salesperson = User::where('company_id', 1)->where('role', 'salesperson')->first();
        $engagement = LeadEngagement::where('company_id', 1)->where('assigned_user_id', $salesperson->id)->first();
        
        if (!$engagement) {
            $engagement = LeadEngagement::where('company_id', 1)->first();
            $engagement->assigned_user_id = $salesperson->id;
            $engagement->save();
        }

        $response = $this->actingAs($salesperson)->getJson('/api/leads/' . $engagement->id);
        $response->assertStatus(200);
    }

    public function test_team_lead_can_view_team_leads_but_not_other_teams()
    {
        $tl = User::where('company_id', 1)->where('role', 'team_lead')->first();
        $teamSalesperson = User::where('company_id', 1)->where('team_id', $tl->team_id)->where('role', 'salesperson')->first();
        
        // Find a salesperson in a different team
        $otherTeamSalesperson = User::where('company_id', 1)->whereNotNull('team_id')->where('team_id', '!=', $tl->team_id)->where('role', 'salesperson')->first();

        $teamEng = LeadEngagement::where('assigned_user_id', $teamSalesperson->id)->first();
        if (!$teamEng) {
            $teamEng = LeadEngagement::where('company_id', 1)->first();
            $teamEng->assigned_user_id = $teamSalesperson->id;
            $teamEng->save();
        }

        $otherEng = LeadEngagement::where('assigned_user_id', $otherTeamSalesperson->id)->first();
        if (!$otherEng) {
            $otherEng = LeadEngagement::where('company_id', 1)->where('id', '!=', $teamEng->id)->first();
            $otherEng->assigned_user_id = $otherTeamSalesperson->id;
            $otherEng->save();
        }

        $this->actingAs($tl)->getJson('/api/leads/' . $teamEng->id)->assertStatus(200);
        $this->actingAs($tl)->getJson('/api/leads/' . $otherEng->id)->assertStatus(403);
    }

    public function test_manager_can_view_any_engagement_in_company()
    {
        $manager = User::where('company_id', 1)->where('role', 'manager')->first();
        $engagement = LeadEngagement::where('company_id', 1)->first();

        $this->actingAs($manager)->getJson('/api/leads/' . $engagement->id)->assertStatus(200);
    }
}

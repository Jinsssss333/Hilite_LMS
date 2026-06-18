<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadEngagement;

class DuplicateExposureTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFreshDatabase();
    }

    public function test_unassigned_duplicate_does_not_leak_assigned_to()
    {
        $company = Company::find(1);
        $user = User::where('company_id', 1)->where('role', 'salesperson')->first();
        
        $engagement = LeadEngagement::where('company_id', 1)->whereNull('assigned_user_id')->first();
        $this->assertNotNull($engagement);
        
        $phone = $engagement->lead->phone_e164;

        $response = $this->actingAs($user)->postJson('/api/leads', [
            'name' => 'Duplicate Name',
            'phone' => $phone,
            'source' => 'manual'
        ]);

        $response->assertStatus(200);
        $this->assertNull($response->json('data.assigned_to'));
    }

    public function test_own_duplicate_shows_own_info()
    {
        $user = User::where('company_id', 1)->where('role', 'salesperson')->first();
        
        $engagement = LeadEngagement::where('company_id', 1)->where('assigned_user_id', $user->id)->first();
        $phone = $engagement->lead->phone_e164;

        $response = $this->actingAs($user)->postJson('/api/leads', [
            'name' => 'Duplicate Name',
            'phone' => $phone,
            'source' => 'manual'
        ]);

        $response->assertStatus(200);
        $this->assertEquals($user->id, $response->json('data.assigned_to.id'));
    }

    public function test_colleague_duplicate_returns_409_with_name()
    {
        $user = User::where('company_id', 1)->where('role', 'salesperson')->first();
        $colleague = User::where('company_id', 1)->where('role', 'salesperson')->where('id', '!=', $user->id)->first();
        
        $engagement = LeadEngagement::where('company_id', 1)->where('assigned_user_id', $colleague->id)->first();
        $phone = $engagement->lead->phone_e164;

        $response = $this->actingAs($user)->postJson('/api/leads', [
            'name' => 'Duplicate Name',
            'phone' => $phone,
            'source' => 'manual'
        ]);

        $response->assertStatus(409);
        $this->assertStringContainsString($colleague->name, $response->json('message'));
    }

    public function test_check_duplicate_hides_assignee_for_salesperson()
    {
        $user = User::where('company_id', 1)->where('role', 'salesperson')->first();
        $colleague = User::where('company_id', 1)->where('role', 'salesperson')->where('id', '!=', $user->id)->first();
        
        $engagement = LeadEngagement::where('company_id', 1)->where('assigned_user_id', $colleague->id)->first();
        $phone = $engagement->lead->phone_e164;

        $response = $this->actingAs($user)->getJson('/api/leads/check-duplicate?phone=' . urlencode($phone));

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.exists'));
        $this->assertTrue($response->json('data.engagement_exists_in_company'));
        $this->assertTrue($response->json('data.is_assigned'));
        $this->assertNull($response->json('data.assigned_to'));
        $this->assertNull($response->json('data.stage'));
    }

    public function test_check_duplicate_shows_assignee_for_team_lead()
    {
        $tl = User::where('company_id', 1)->where('role', 'team_lead')->first();
        
        // Find an engagement assigned to someone in their company
        $engagement = LeadEngagement::where('company_id', 1)->whereNotNull('assigned_user_id')->first();
        $phone = $engagement->lead->phone_e164;

        $response = $this->actingAs($tl)->getJson('/api/leads/check-duplicate?phone=' . urlencode($phone));

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.exists'));
        $this->assertTrue($response->json('data.engagement_exists_in_company'));
        $this->assertTrue($response->json('data.is_assigned'));
        $this->assertNotNull($response->json('data.assigned_to'));
        $this->assertNotNull($response->json('data.stage'));
    }
}

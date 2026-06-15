<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadEngagement;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_company_a_user_cannot_see_company_b_engagements_in_index()
    {
        $userA = User::where('company_id', 1)->where('role', 'admin')->first(); // Admin sees all in their company
        $userB = User::where('company_id', 2)->where('role', 'admin')->first();

        // Ensure both companies have leads
        $this->assertTrue(LeadEngagement::where('company_id', 1)->exists());
        $this->assertTrue(LeadEngagement::where('company_id', 2)->exists());

        $responseA = $this->actingAs($userA)->getJson('/api/leads');
        $responseA->assertStatus(200);
        
        $dataA = $responseA->json('data');
        foreach ($dataA as $item) {
            // Check that engagement belongs to company 1
            $eng = LeadEngagement::find($item['engagement_id']);
            $this->assertEquals(1, $eng->company_id);
        }

        $responseB = $this->actingAs($userB)->getJson('/api/leads');
        $responseB->assertStatus(200);
        
        $dataB = $responseB->json('data');
        foreach ($dataB as $item) {
            $eng = LeadEngagement::find($item['engagement_id']);
            $this->assertEquals(2, $eng->company_id);
        }
    }

    public function test_company_a_user_cannot_see_company_b_engagement_in_show()
    {
        $userA = User::where('company_id', 1)->where('role', 'admin')->first();
        $engagementB = LeadEngagement::where('company_id', 2)->first();

        $response = $this->actingAs($userA)->getJson('/api/leads/' . $engagementB->id);
        $response->assertStatus(404); // Global scope should hide it completely, resulting in 404 Model Not Found
    }
}

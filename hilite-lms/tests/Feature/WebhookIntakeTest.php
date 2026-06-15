<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Company;
use App\Models\StagingLead;
use Illuminate\Support\Facades\Config;

class WebhookIntakeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        Config::set('app.webhook_secret_key', 'test_secret_key');
    }

    public function test_webhook_without_key_returns_401()
    {
        $response = $this->postJson('/api/webhooks/leads', [
            'name' => 'Test Lead',
            'phone' => '1234567890',
            'company_slug' => 'hilite-builders'
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_with_wrong_key_returns_401()
    {
        $response = $this->postJson('/api/webhooks/leads', [
            'name' => 'Test Lead',
            'phone' => '1234567890',
            'company_slug' => 'hilite-builders'
        ], [
            'X-Webhook-Key' => 'wrong_key'
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_with_unknown_slug_returns_404()
    {
        $response = $this->postJson('/api/webhooks/leads', [
            'name' => 'Test Lead',
            'phone' => '1234567890',
            'company_slug' => 'unknown-slug'
        ], [
            'X-Webhook-Key' => 'test_secret_key'
        ]);

        $response->assertStatus(404);
    }

    public function test_webhook_with_valid_payload_creates_staging_row()
    {
        $response = $this->postJson('/api/webhooks/leads', [
            'name' => 'Webhook Lead',
            'phone' => '1112223334',
            'company_slug' => 'hilite-builders',
            'source' => 'meta_ads'
        ], [
            'X-Webhook-Key' => 'test_secret_key'
        ]);

        $response->assertStatus(202);
        
        $this->assertDatabaseHas('staging_leads', [
            'raw_phone' => '1112223334',
            'source' => 'meta_ads'
        ]);
    }

    public function test_duplicate_webhook_payload_does_not_create_second_row()
    {
        $payload = [
            'name' => 'Webhook Lead',
            'phone' => '1112223334',
            'company_slug' => 'hilite-builders',
            'source' => 'meta_ads'
        ];

        $response1 = $this->postJson('/api/webhooks/leads', $payload, ['X-Webhook-Key' => 'test_secret_key']);
        $response1->assertStatus(202);

        $response2 = $this->postJson('/api/webhooks/leads', $payload, ['X-Webhook-Key' => 'test_secret_key']);
        $response2->assertStatus(202);

        $this->assertEquals(1, StagingLead::where('raw_phone', '1112223334')->count());
    }
}

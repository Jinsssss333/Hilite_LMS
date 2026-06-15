<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_login_rate_limit()
    {
        // 10 requests allowed per minute
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'admin@hilitebuilders.com',
                'password' => 'wrong'
            ]);
            $response->assertStatus(401);
        }

        // 11th request should be rate limited
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@hilitebuilders.com',
            'password' => 'wrong'
        ]);
        $response->assertStatus(429);
    }
    
    // Additional rate limit tests (POST /leads, POST /leads/import) can be similar
    // Using $this->actingAs($user)->postJson(...) 
}

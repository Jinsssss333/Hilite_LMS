<?php
namespace Tests\Unit;

use App\Services\PhoneNormalizationService;
use PHPUnit\Framework\TestCase;

class PhoneNormalizationTest extends TestCase
{
    protected PhoneNormalizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PhoneNormalizationService();
    }

    public function test_indian_10_digit_without_prefix_normalizes()
    {
        $this->assertEquals('+919876543210', $this->service->normalize('9876543210'));
    }

    public function test_indian_number_with_0_prefix_normalizes()
    {
        $this->assertEquals('+919876543210', $this->service->normalize('09876543210'));
    }

    public function test_uae_number_normalizes()
    {
        $this->assertEquals('+971501234567', $this->service->normalize('+971501234567'));
        $this->assertEquals('+971501234567', $this->service->normalize('0501234567')); // AE parsing handles 050
    }

    public function test_garbage_string_returns_null()
    {
        $this->assertNull($this->service->normalize('not-a-phone-number'));
    }

    public function test_empty_string_returns_null()
    {
        $this->assertNull($this->service->normalize(''));
    }
}

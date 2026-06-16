<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Indicates whether the test database has been freshly migrated and seeded
     * for the current test run. We do this once per process (not per test) to
     * avoid the auto-increment drift issue with MySQL + RefreshDatabase.
     */
    protected static bool $dbSeeded = false;

    /**
     * Sets up a clean, seeded database once per test run.
     * Each individual test is wrapped in a transaction (via DatabaseTransactions
     * on the child class) so state does not leak between tests.
     */
    protected function setUpFreshDatabase(): void
    {
        if (!static::$dbSeeded) {
            // Disable FK checks so truncate works even with foreign keys
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $this->artisan('migrate:fresh --seed');

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            static::$dbSeeded = true;
        }
    }
}

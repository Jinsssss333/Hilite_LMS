<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     * Dev C owns all Gate definitions used for assignment, audit, and SLA access.
     */
    public function boot(): void
    {
        // Who can assign leads to anyone in the company
        Gate::define('assign-leads-globally', fn($user) =>
            in_array($user->role, ['admin', 'super_admin', 'manager'])
        );

        // Who can assign leads within their own team only
        Gate::define('assign-leads-in-team', fn($user) =>
            in_array($user->role, ['team_lead', 'admin', 'super_admin', 'manager'])
        );

        // Who can view audit logs
        Gate::define('view-audit-logs', fn($user) =>
            in_array($user->role, ['admin', 'super_admin', 'manager', 'branch_head'])
        );

        // Who can manage SLA policies
        Gate::define('manage-sla-policies', fn($user) =>
            in_array($user->role, ['admin', 'super_admin'])
        );

        // Who can view the full admin user list
        Gate::define('view-admin-users', fn($user) =>
            in_array($user->role, ['admin', 'super_admin', 'manager', 'branch_head', 'team_lead'])
        );
    }
}

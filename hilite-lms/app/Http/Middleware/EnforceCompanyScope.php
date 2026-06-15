<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnforceCompanyScope
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->company_id) {
            app()->instance('current_company_id', $user->company_id);
            app()->instance('current_user', $user);
        }
        return $next($request);
    }
}

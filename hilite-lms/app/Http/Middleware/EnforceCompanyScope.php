<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Helpers\AuthHelper;
use Illuminate\Support\Facades\DB;

class EnforceCompanyScope
{
    public function handle(Request $request, Closure $next)
    {
        // Use custom session-based AuthHelper, NOT $request->user() (which uses Laravel Auth guard)
        $user = AuthHelper::user();
        if ($user) {
            $companyId = $user->company_id
                ?? DB::table('branches')->where('id', $user->branch_id)->value('company_id');

            if ($companyId) {
                app()->instance('current_company_id', $companyId);
                app()->instance('current_user', $user);
            }
        }
        return $next($request);
    }
}

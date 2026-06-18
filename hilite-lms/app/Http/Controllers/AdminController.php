<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PipelineStage;
use App\Models\SlaPolicy;
use App\Models\AuditLog;
use App\Http\Helpers\AuthHelper;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    private function getCompanyId()
    {
        $user = AuthHelper::user();
        if (!$user) return null;
        return $user->company_id ?? DB::table('branches')->where('id', $user->branch_id)->value('company_id');
    }

    public function index()
    {
        return view('admin.index');
    }

    public function users()
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return redirect()->route('login');

        // Note: Using eager loading if Team/Branch relationships exist, otherwise left joins.
        // Assuming user belongsTo team and branch.
        $users = User::where('company_id', $companyId)
            ->leftJoin('teams', 'users.team_id', '=', 'teams.id')
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.id')
            ->select('users.*', 'teams.name as team_name', 'branches.name as branch_name')
            ->orderBy('users.name')
            ->get();

        return view('admin.users', compact('users'));
    }

    public function pipeline()
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return redirect()->route('login');

        $stages = PipelineStage::where('company_id', $companyId)
            ->orderBy('order')
            ->get();

        return view('admin.pipeline', compact('stages'));
    }

    public function sla()
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return redirect()->route('login');

        $policies = SlaPolicy::where('company_id', $companyId)->get();

        return view('admin.sla', compact('policies'));
    }

    public function audit()
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return redirect()->route('login');

        $logs = AuditLog::where('company_id', $companyId)
            ->leftJoin('users', 'audit_logs.actor_user_id', '=', 'users.id')
            ->select('audit_logs.*', 'users.name as actor_name')
            ->orderByDesc('audit_logs.created_at')
            ->paginate(50);

        return view('admin.audit', compact('logs'));
    }
}

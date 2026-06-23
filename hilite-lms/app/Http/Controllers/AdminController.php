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
        $users = User::where('users.company_id', $companyId)
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

        $policies = SlaPolicy::where('company_id', $companyId)->with('stage')->get();
        $stages = PipelineStage::where('company_id', $companyId)->orderBy('order')->get();

        return view('admin.sla', compact('policies', 'stages'));
    }

    public function audit()
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return redirect()->route('login');

        $logs = \App\Models\AuditLog::with(['company', 'actor'])->where('company_id', $companyId)->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.audit', compact('logs'));
    }

    public function exportAudit()
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return redirect()->route('login');

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=audit_logs.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Timestamp', 'Actor', 'Action', 'Entity Type', 'Entity ID', 'Details'];

        $callback = function() use($companyId, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            \App\Models\AuditLog::where('company_id', $companyId)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->chunk(100, function ($logs) use ($file) {
                    foreach ($logs as $log) {
                        fputcsv($file, [
                            $log->created_at->format('Y-m-d H:i:s'),
                            $log->user->name ?? 'System',
                            $log->action,
                            $log->entity_type,
                            $log->entity_id,
                            json_encode($log->after_state)
                        ]);
                    }
                });
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // --- USER MANAGEMENT ---

    public function storeUser(Request $request)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return redirect()->route('login');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:super_admin,admin,manager,branch_head,team_lead,salesperson',
        ]);

        $user = User::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        AuditLog::log($companyId, AuthHelper::user()->id ?? 0, 'user', $user->id, 'created', 'Created user ' . $user->name);

        return back()->with('success', 'User created successfully.');
    }

    public function updateUser(Request $request, $id)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return redirect()->route('login');

        $user = User::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:super_admin,admin,manager,branch_head,team_lead,salesperson',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        AuditLog::log($companyId, AuthHelper::user()->id ?? 0, 'user', $user->id, 'updated', 'Updated user ' . $user->name);

        return back()->with('success', 'User updated successfully.');
    }

    public function toggleUser($id)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return redirect()->route('login');

        $user = User::where('company_id', $companyId)->findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        AuditLog::log($companyId, AuthHelper::user()->id ?? 0, 'user', $user->id, 'toggled', 'Toggled user ' . $user->name . ' active state to ' . ($user->is_active ? 'Active' : 'Inactive'));

        return back()->with('success', 'User status toggled.');
    }

    // --- PIPELINE STAGES ---

    public function storeStage(Request $request)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return redirect()->route('login');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:10',
            'order' => 'required|integer',
        ]);

        $stage = PipelineStage::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#6366f1',
            'order' => $validated['order'],
            'is_closed' => $request->has('is_closed'),
        ]);

        AuditLog::log($companyId, AuthHelper::user()->id ?? 0, 'pipeline_stage', $stage->id, 'created', 'Created stage ' . $stage->name);

        return back()->with('success', 'Stage created successfully.');
    }

    public function updateStage(Request $request, $id)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return redirect()->route('login');

        $stage = PipelineStage::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:10',
            'order' => 'required|integer',
        ]);

        $stage->update([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#6366f1',
            'order' => $validated['order'],
            'is_closed' => $request->has('is_closed'),
        ]);

        AuditLog::log($companyId, AuthHelper::user()->id ?? 0, 'pipeline_stage', $stage->id, 'updated', 'Updated stage ' . $stage->name);

        return back()->with('success', 'Stage updated successfully.');
    }

    public function deleteStage($id)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return redirect()->route('login');

        $stage = PipelineStage::where('company_id', $companyId)->findOrFail($id);
        
        // Ensure not in use
        if (\App\Models\LeadEngagement::where('stage_id', $stage->id)->exists()) {
            return back()->with('error', 'Cannot delete stage: It is currently in use by active leads.');
        }

        $stageName = $stage->name;
        $stage->delete();

        AuditLog::log($companyId, AuthHelper::user()->id ?? 0, 'pipeline_stage', $id, 'deleted', 'Deleted stage ' . $stageName);

        return back()->with('success', 'Stage deleted successfully.');
    }

    // --- SLA POLICIES ---

    public function storeSla(Request $request)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return redirect()->route('login');

        $validated = $request->validate([
            'stage_id' => 'required|exists:pipeline_stages,id',
            'sla_days' => 'required|integer|min:1',
            'escalate_to_role' => 'nullable|string|in:manager,team_lead,branch_head,admin',
        ]);

        $policy = SlaPolicy::create([
            'company_id' => $companyId,
            'stage_id' => $validated['stage_id'],
            'sla_days' => $validated['sla_days'],
            'escalate_to_role' => $validated['escalate_to_role'],
        ]);

        AuditLog::log($companyId, AuthHelper::user()->id ?? 0, 'sla_policy', $policy->id, 'created', 'Created SLA Policy for stage ID ' . $policy->stage_id);

        return back()->with('success', 'SLA Policy created successfully.');
    }

    public function updateSla(Request $request, $id)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return redirect()->route('login');

        $policy = SlaPolicy::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'stage_id' => 'required|exists:pipeline_stages,id',
            'sla_days' => 'required|integer|min:1',
            'escalate_to_role' => 'nullable|string|in:manager,team_lead,branch_head,admin',
        ]);

        $policy->update([
            'stage_id' => $validated['stage_id'],
            'sla_days' => $validated['sla_days'],
            'escalate_to_role' => $validated['escalate_to_role'],
        ]);

        AuditLog::log($companyId, AuthHelper::user()->id ?? 0, 'sla_policy', $policy->id, 'updated', 'Updated SLA Policy for stage ID ' . $policy->stage_id);

        return back()->with('success', 'SLA Policy updated successfully.');
    }

    public function deleteSla($id)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return redirect()->route('login');

        $policy = SlaPolicy::where('company_id', $companyId)->findOrFail($id);
        $policy->delete();

        AuditLog::log($companyId, AuthHelper::user()->id ?? 0, 'sla_policy', $id, 'deleted', 'Deleted SLA Policy');

        return back()->with('success', 'SLA Policy deleted successfully.');
    }
}

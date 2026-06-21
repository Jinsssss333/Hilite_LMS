<?php

namespace App\Http\Controllers;

use App\Http\Helpers\AuthHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadsController extends Controller
{
    public function index(Request $request)
    {
        $user = AuthHelper::user();
        $role = $user->role;

        // Resolve company_id for manager role (may be on branch, not directly on user)
        $companyId = $user->company_id
            ?? DB::table('branches')->where('id', $user->branch_id)->value('company_id');

        // Build base query: leads joined with their engagement
        $query = DB::table('lead_engagements as le')
            ->join('leads as l', 'le.lead_id', '=', 'l.id')
            ->join('pipeline_stages as ps', 'le.stage_id', '=', 'ps.id')
            ->leftJoin('users as u', 'le.assigned_user_id', '=', 'u.id')
            ->select(
                'l.id', 'l.name', 'l.phone_e164', 'l.email', 'l.status', 'l.is_shared_number',
                'l.created_at',
                'le.id as engagement_id', 'le.stage_id', 'le.source',
                'le.last_activity_at', 'le.sla_breached', 'le.sla_due_at',
                'ps.name as stage_name', 'ps.color as stage_color',
                'u.name as assigned_name', 'u.id as assigned_id',
                'le.company_id'
            );

        // Role-based scoping
        match ($role) {
            'admin'       => null, // sees all
            'manager'     => $query->where('le.company_id', $companyId),
            'branch_head' => $query->whereIn(
                'le.assigned_user_id',
                DB::table('users')->where('branch_id', $user->branch_id)->pluck('id')->toArray()
            ),
            'team_lead'   => $query->whereIn(
                'le.assigned_user_id',
                DB::table('users')->where('team_id', $user->team_id)->pluck('id')->toArray()
            ),
            'salesperson' => $query->where('le.assigned_user_id', $user->id),
            default       => $query->whereRaw('1=0'), // no access
        };

        // Filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('l.name', 'LIKE', "%{$search}%")
                  ->orWhere('l.phone_e164', 'LIKE', "%{$search}%");
            });
        }

        if ($region = $request->get('region')) {
            if ($region === 'india') $query->where('l.phone_e164', 'LIKE', '+91%');
            elseif ($region === 'uae') $query->where('l.phone_e164', 'LIKE', '+971%');
            elseif ($region === 'us') $query->where('l.phone_e164', 'LIKE', '+1%');
            elseif ($region === 'uk') $query->where('l.phone_e164', 'LIKE', '+44%');
        }

        if ($date = $request->get('date')) {
            $now = \Carbon\Carbon::now();
            if ($date === 'today') {
                $query->whereDate('l.created_at', $now->toDateString());
            } elseif ($date === 'this_week') {
                $query->whereBetween('l.created_at', [$now->startOfWeek()->toDateString(), $now->endOfWeek()->toDateString()]);
            } elseif ($date === 'this_month') {
                $query->whereMonth('l.created_at', $now->month)
                      ->whereYear('l.created_at', $now->year);
            }
        }

        if (($assignedTo = $request->get('assigned_to')) && in_array($role, ['admin', 'manager', 'branch_head', 'team_lead'])) {
            $query->where('le.assigned_user_id', $assignedTo);
        }

        if ($stageId = $request->get('stage_id')) {
            $query->where('le.stage_id', $stageId);
        }

        $leads = $query->orderByDesc('le.last_activity_at')->paginate(20)->withQueryString();

        // Stats
        $totalLeads  = $leads->total();
        $slaBreaches = (clone $query)->where('le.sla_breached', 1)->count();

        // For filter dropdowns: only show salespersons the current user can see
        $assignableUsers = match ($role) {
            'admin', 'manager' => User::whereIn('role', ['salesperson', 'team_lead'])->get(['id', 'name', 'team_id']),
            'branch_head'      => User::where('branch_id', $user->branch_id)
                ->whereIn('role', ['salesperson', 'team_lead'])
                ->get(['id', 'name', 'team_id']),
            'team_lead'        => User::where('team_id', $user->team_id)->get(['id', 'name', 'team_id']),
            default            => collect(),
        };

        $stages = DB::table('pipeline_stages')->select('id', 'name', 'color')->get();
        $canEditStatus   = true; // All roles can update stage on leads within their visibility scope
        $canSeeFullPhone = in_array($role, ['admin', 'manager', 'branch_head', 'team_lead', 'salesperson']);
        $canFlagShared   = in_array($role, ['admin', 'manager', 'branch_head']);

        return view('leads.index', compact(
            'leads', 'user', 'role', 'stages', 'assignableUsers',
            'totalLeads', 'slaBreaches', 'canEditStatus', 'canSeeFullPhone', 'canFlagShared'
        ));
    }

    public function updateStage(Request $request, $id)
    {
        $user = AuthHelper::user();
        if (!in_array($user->role, ['team_lead', 'salesperson'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Only executives and TLs can update lead status.'], 403);
        }

        $request->validate([
            'stage_id' => 'required|exists:pipeline_stages,id'
        ]);

        $engagement = \App\Models\LeadEngagement::find($id);
        if (!$engagement) {
            return response()->json(['success' => false, 'message' => 'Lead engagement not found'], 404);
        }

        // Verify assignment ownership based on role
        if ($user->role === 'salesperson' && $engagement->assigned_user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Cannot update a lead not assigned to you.'], 403);
        }
        if ($user->role === 'team_lead') {
            $teamUserIds = DB::table('users')->where('team_id', $user->team_id)->pluck('id')->toArray();
            if (!in_array($engagement->assigned_user_id, $teamUserIds)) {
                return response()->json(['success' => false, 'message' => 'Cannot update a lead not in your team.'], 403);
            }
        }

        $engagement->stage_id = $request->stage_id;
        $engagement->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully']);
    }

    public function processImport(Request $request, \App\Services\LeadIntakeService $intakeService)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB
        ]);

        $user = AuthHelper::user();
        if (!$user) return redirect()->route('login');
        $companyId = $user->company_id ?? DB::table('branches')->where('id', $user->branch_id)->value('company_id');

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $data = array_map('str_getcsv', file($path));
        if (count($data) < 2) {
            return back()->with('error', 'CSV file is empty or missing headers.');
        }

        $headers = array_shift($data);
        $headers = array_map('trim', array_map('strtolower', $headers));

        // required: name, phone
        $nameIdx = array_search('name', $headers);
        $phoneIdx = array_search('phone', $headers);
        $emailIdx = array_search('email', $headers);
        $sourceIdx = array_search('source', $headers);
        $notesIdx = array_search('notes', $headers);

        if ($nameIdx === false || $phoneIdx === false) {
            return back()->with('error', 'CSV must contain "name" and "phone" columns.');
        }

        $success = 0;
        $duplicates = 0;
        $failed = 0;

        foreach ($data as $row) {
            if (count($row) !== count($headers)) {
                $failed++;
                continue;
            }

            $input = [
                'name' => trim($row[$nameIdx]),
                'phone' => trim($row[$phoneIdx]),
                'email' => $emailIdx !== false ? trim($row[$emailIdx]) : null,
                'source' => $sourceIdx !== false ? trim($row[$sourceIdx]) : 'csv',
                'assigned_user_id' => null, // Auto-assign via policy
            ];

            if (empty($input['name']) || empty($input['phone'])) {
                $failed++;
                continue;
            }

            try {
                $result = $intakeService->intake($input, $companyId, $user->id);
                if ($result['is_duplicate']) {
                    $duplicates++;
                } else {
                    $success++;
                    
                    if ($notesIdx !== false && !empty(trim($row[$notesIdx]))) {
                        \App\Models\Activity::create([
                            'engagement_id' => $result['engagement']->id,
                            'created_by_user_id' => $user->id,
                            'type' => 'note',
                            'notes' => trim($row[$notesIdx]),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return back()->with('success', "Import complete: $success created, $duplicates duplicates, $failed failed.");
    }

    public function processManual(Request $request, \App\Services\LeadIntakeService $intakeService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'source' => 'required|string',
            'assigned_user_id' => 'nullable',
            'notes' => 'nullable|string',
        ]);

        $user = AuthHelper::user();
        if (!$user) return redirect()->route('login');
        $companyId = $user->company_id ?? DB::table('branches')->where('id', $user->branch_id)->value('company_id');

        $assignedUserId = $request->assigned_user_id === 'unassigned' ? null : $request->assigned_user_id;

        try {
            $result = $intakeService->intake([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'source' => $request->source,
                'assigned_user_id' => $assignedUserId,
            ], $companyId, $user->id);

            if ($result['is_duplicate']) {
                return back()->with('error', 'Lead with this phone number already exists.');
            }

            if ($request->filled('notes')) {
                \App\Models\Activity::create([
                    'engagement_id' => $result['engagement']->id,
                    'created_by_user_id' => $user->id,
                    'type' => 'note',
                    'notes' => $request->notes,
                ]);
            }

            return back()->with('success', 'Lead created successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create lead: ' . $e->getMessage());
        }
    }

    /**
     * PATCH /leads/{id}/flag-shared
     * Marks a lead's phone number as shared (corporate switchboard, generic email etc.)
     * so the intake service creates separate engagements for each unique person.
     * Allowed roles: admin, manager, branch_head only.
     */
    public function flagShared(Request $request, $id)
    {
        $user = AuthHelper::user();
        if (!in_array($user->role, ['admin', 'super_admin', 'manager', 'branch_head'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate(['is_shared' => 'required|boolean']);

        $engagement = \App\Models\LeadEngagement::with('lead')->find($id);
        if (!$engagement) {
            return response()->json(['success' => false, 'message' => 'Lead not found.'], 404);
        }

        $engagement->lead->update(['is_shared_number' => $request->boolean('is_shared')]);

        return response()->json([
            'success' => true,
            'message' => $request->boolean('is_shared')
                ? 'Flagged as shared number. Future intakes on this phone will create separate leads.'
                : 'Shared flag removed. Deduplication is now active for this number again.',
        ]);
    }

    public function dispositions()
    {
        // Return dispositions for the company
        $companyId = AuthHelper::user()->company_id;
        // Check if there are any, else return dummy
        $dispositions = \App\Models\Disposition::where('company_id', $companyId)->get();
        if ($dispositions->isEmpty()) {
            // For prototyping if empty
            return response()->json([
                ['id' => 1, 'label' => '✅ Connected / Spoke to Lead'],
                ['id' => 2, 'label' => '❌ No Answer / Busy'],
                ['id' => 3, 'label' => '📅 Requested Callback'],
                ['id' => 4, 'label' => '🚫 Not Interested'],
            ]);
        }
        return response()->json($dispositions);
    }

    public function logActivity(Request $request, $id)
    {
        $user = AuthHelper::user();
        
        $request->validate([
            'disposition_id' => 'required', // could be string if dummy
            'notes' => 'nullable|string',
            'follow_up_at' => 'nullable|date',
        ]);

        $engagement = \App\Models\LeadEngagement::findOrFail($id);

        $activity = \App\Models\Activity::create([
            'engagement_id' => $engagement->id,
            'created_by_user_id' => $user->id,
            'disposition_id' => is_numeric($request->disposition_id) ? $request->disposition_id : null,
            'type' => 'call',
            'notes' => $request->notes,
            'follow_up_at' => $request->follow_up_at,
        ]);

        // If follow_up_at is provided, engagement needs last_activity_at updated
        $engagement->update(['last_activity_at' => now()]);

        return response()->json(['success' => true, 'activity' => $activity, 'message' => 'Activity logged successfully.']);
    }
}

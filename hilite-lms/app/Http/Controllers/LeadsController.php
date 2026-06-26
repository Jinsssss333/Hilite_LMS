<?php

namespace App\Http\Controllers;

use App\Http\Helpers\AuthHelper;
use App\Models\User;
use App\Models\LeadEngagement;
use App\Models\Activity;
use App\Models\Disposition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
                  ->orWhere('l.phone_e164', 'LIKE', "%{$search}%")
                  ->orWhere('l.email', 'LIKE', "%{$search}%");
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

        $leads = $query->orderByDesc('le.last_activity_at')->simplePaginate(20)->withQueryString();

        // Stats
        $totalLeads  = null; // Cannot use total() with simplePaginate
        $slaBreaches = (clone $query)->where('le.sla_breached', 1)->count();

        // Bento card stats
        $unassignedCount = DB::table('lead_engagements')
            ->whereNull('assigned_user_id')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->count();

        $followupsToday = DB::table('activities')
            ->join('lead_engagements', 'activities.engagement_id', '=', 'lead_engagements.id')
            ->where('lead_engagements.company_id', $companyId)
            ->whereDate('activities.follow_up_at', now()->toDateString())
            ->count();

        // Average lead speed (minutes from creation to first activity)
        $avgMinutes = DB::table('activities')
            ->join('lead_engagements', 'activities.engagement_id', '=', 'lead_engagements.id')
            ->where('lead_engagements.company_id', $companyId)
            ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, lead_engagements.created_at, activities.created_at)'));
        $avgSpeed = $avgMinutes ? round($avgMinutes) . 'm' : '—';

        // For filter dropdowns: only show salespersons the current user can see
        $assignableUsers = match ($role) {
            'admin', 'manager' => User::whereIn('role', ['salesperson', 'team_lead'])->get(['id', 'name', 'team_id']),
            'branch_head'      => User::where('branch_id', $user->branch_id)
                ->whereIn('role', ['salesperson', 'team_lead'])
                ->get(['id', 'name', 'team_id']),
            'team_lead'        => User::where('team_id', $user->team_id)->get(['id', 'name', 'team_id']),
            default            => collect(),
        };

        $stages = DB::table('pipeline_stages')->where('company_id', $companyId)->select('id', 'name', 'color')->get();
        $canEditStatus   = true; // All roles can update stage on leads within their visibility scope
        $canSeeFullPhone = in_array($role, ['admin', 'manager', 'branch_head', 'team_lead']);
        $canFlagShared   = in_array($role, ['admin', 'manager', 'branch_head']);

        return view('leads.index', compact(
            'leads', 'user', 'role', 'stages', 'assignableUsers',
            'totalLeads', 'slaBreaches', 'canEditStatus', 'canSeeFullPhone', 'canFlagShared',
            'unassignedCount', 'followupsToday', 'avgSpeed'
        ));
    }

    public function export(Request $request)
    {
        $user = AuthHelper::user();
        if (!$user) return redirect()->route('login');
        $role = $user->role;

        $companyId = $user->company_id
            ?? DB::table('branches')->where('id', $user->branch_id)->value('company_id');

        // Same base query as index — full data for CSV (no pagination)
        $query = DB::table('lead_engagements as le')
            ->join('leads as l', 'le.lead_id', '=', 'l.id')
            ->join('pipeline_stages as ps', 'le.stage_id', '=', 'ps.id')
            ->leftJoin('users as u', 'le.assigned_user_id', '=', 'u.id')
            ->select(
                'l.name',
                'l.phone_e164',
                'l.email',
                'ps.name as stage_name',
                'le.source',
                'u.name as assigned_to',
                'le.sla_breached',
                'le.sla_due_at',
                'le.last_activity_at',
                'l.created_at as lead_created_at',
                'l.status as lead_status'
            );

        // Role-based scoping (mirrors index)
        match ($role) {
            'admin'       => null,
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
            default       => $query->whereRaw('1=0'),
        };

        // Apply same filters from request
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('l.name', 'LIKE', "%{$search}%")
                  ->orWhere('l.phone_e164', 'LIKE', "%{$search}%")
                  ->orWhere('l.email', 'LIKE', "%{$search}%");
            });
        }
        if ($region = $request->get('region')) {
            if ($region === 'india') $query->where('l.phone_e164', 'LIKE', '+91%');
            elseif ($region === 'uae') $query->where('l.phone_e164', 'LIKE', '+971%');
            elseif ($region === 'us')  $query->where('l.phone_e164', 'LIKE', '+1%');
            elseif ($region === 'uk')  $query->where('l.phone_e164', 'LIKE', '+44%');
        }
        if ($date = $request->get('date')) {
            $now = Carbon::now();
            if ($date === 'today')       $query->whereDate('l.created_at', $now->toDateString());
            elseif ($date === 'this_week')  $query->whereBetween('l.created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
            elseif ($date === 'this_month') $query->whereMonth('l.created_at', $now->month)->whereYear('l.created_at', $now->year);
        }
        if (($assignedTo = $request->get('assigned_to')) && in_array($role, ['admin', 'manager', 'branch_head', 'team_lead'])) {
            $query->where('le.assigned_user_id', $assignedTo);
        }
        if ($stageId = $request->get('stage_id')) {
            $query->where('le.stage_id', $stageId);
        }

        $filename = 'hilite_leads_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        // Phone formatter for display
        $canSeeFullPhone = in_array($role, ['admin', 'manager', 'branch_head', 'team_lead']);

        $callback = function () use ($query, $canSeeFullPhone) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens correctly
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, [
                'Name',
                'Phone',
                'Email',
                'Stage',
                'Source',
                'Assigned To',
                'SLA Status',
                'SLA Due',
                'Last Activity',
                'Lead Created',
                'Status',
            ]);

            // Stream rows in chunks of 200 to avoid memory exhaustion
            $query->orderByDesc('le.last_activity_at')->chunk(200, function ($rows) use ($handle, $canSeeFullPhone) {
                foreach ($rows as $row) {
                    $phone = $canSeeFullPhone
                        ? $row->phone_e164
                        : (strlen($row->phone_e164) >= 6
                            ? substr($row->phone_e164, 0, 4) . '*** **' . substr($row->phone_e164, -3)
                            : '***');

                    fputcsv($handle, [
                        $row->name,
                        $phone,
                        $row->email ?? '',
                        $row->stage_name,
                        ucfirst($row->source ?? ''),
                        $row->assigned_to ?? 'Unassigned',
                        $row->sla_breached ? 'SLA Breached' : 'On Track',
                        $row->sla_due_at  ? Carbon::parse($row->sla_due_at)->format('d M Y H:i')  : '',
                        $row->last_activity_at ? Carbon::parse($row->last_activity_at)->format('d M Y H:i') : '',
                        $row->lead_created_at  ? Carbon::parse($row->lead_created_at)->format('d M Y')  : '',
                        ucfirst($row->lead_status ?? ''),
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function show($id)
    {
        $user = AuthHelper::user();
        if (!$user) {
            return redirect()->route('login');
        }
        $role = $user->role;

        // Fetch engagement with lead, stage, and assigned user
        $engagement = DB::table('lead_engagements as le')
            ->join('leads as l', 'le.lead_id', '=', 'l.id')
            ->join('pipeline_stages as ps', 'le.stage_id', '=', 'ps.id')
            ->leftJoin('users as u', 'le.assigned_user_id', '=', 'u.id')
            ->where('le.id', $id)
            ->where('le.company_id', $user->company_id)
            ->select(
                'l.id as lead_id', 'l.name', 'l.phone_e164', 'l.email', 'l.status', 'l.is_shared_number',
                'l.created_at',
                'le.id as engagement_id', 'le.stage_id', 'le.source',
                'le.last_activity_at', 'le.sla_breached', 'le.sla_due_at',
                'ps.name as stage_name', 'ps.color as stage_color',
                'u.name as assigned_name', 'u.id as assigned_id'
            )->first();

        if (!$engagement) {
            abort(404, 'Lead not found or access denied.');
        }

        // Role-based scoping check
        if ($role === 'branch_head') {
            $branchUserIds = DB::table('users')->where('branch_id', $user->branch_id)->pluck('id')->toArray();
            if ($engagement->assigned_id && !in_array($engagement->assigned_id, $branchUserIds)) {
                abort(403);
            }
        } elseif ($role === 'team_lead') {
            $teamUserIds = DB::table('users')->where('team_id', $user->team_id)->pluck('id')->toArray();
            if ($engagement->assigned_id && !in_array($engagement->assigned_id, $teamUserIds)) {
                abort(403);
            }
        } elseif ($role === 'salesperson') {
            if ($engagement->assigned_id !== $user->id) {
                abort(403);
            }
        }

        $activities = DB::table('activities as a')
            ->leftJoin('users as u', 'a.created_by_user_id', '=', 'u.id')
            ->leftJoin('lead_engagements as eng', 'a.engagement_id', '=', 'eng.id')
            ->leftJoin('pipeline_stages as ps', 'eng.stage_id', '=', 'ps.id')
            ->where('a.engagement_id', $id)
            ->select('a.*', 'u.name as user_name', 'ps.name as stage_name', 'ps.color as stage_color')
            ->orderByDesc('a.created_at')
            ->get();

        $stages = DB::table('pipeline_stages')->where('company_id', $user->company_id)->orderBy('order')->select('id', 'name', 'color')->get();
        
        $assignableUsers = match ($role) {
            'admin', 'manager' => \App\Models\User::where('company_id', $user->company_id)->whereIn('role', ['salesperson', 'team_lead'])->get(['id', 'name']),
            'branch_head'      => \App\Models\User::where('branch_id', $user->branch_id)->whereIn('role', ['salesperson', 'team_lead'])->get(['id', 'name']),
            'team_lead'        => \App\Models\User::where('team_id', $user->team_id)->get(['id', 'name']),
            default            => collect(),
        };

        $canSeeFullPhone = in_array($role, ['admin', 'manager', 'branch_head', 'team_lead']);

        return view('leads.show', compact('engagement', 'activities', 'stages', 'assignableUsers', 'canSeeFullPhone'));
    }
    public function update(Request $request, $id)
    {
        $user = AuthHelper::user();
        if (!$user) return redirect()->route('login');

        // Find the engagement securely without relying on global scope
        $engagement = LeadEngagement::withoutGlobalScopes()
            ->with('lead')
            ->where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        // Ownership check
        if ($user->role === 'salesperson' && $engagement->assigned_user_id !== $user->id) {
            abort(403, 'You can only edit leads assigned to you.');
        }
        if ($user->role === 'team_lead') {
            $teamUserIds = DB::table('users')->where('team_id', $user->team_id)->pluck('id')->toArray();
            if ($engagement->assigned_user_id && !in_array($engagement->assigned_user_id, $teamUserIds)) {
                abort(403, 'You can only edit leads within your team.');
            }
        }

        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'required|string|max:30',
        ]);

        // Normalize the phone number to E.164 before saving
        try {
            $normalizer   = app(\App\Services\PhoneNormalizationService::class);
            $phoneE164    = $normalizer->normalize($request->phone);
        } catch (\Exception $e) {
            return back()->withErrors(['phone' => 'Invalid phone number format. Please enter a valid number with country code (e.g. +91 98765 43210).'])->withInput();
        }

        // Check uniqueness — allow the current lead's own number
        $exists = DB::table('leads')
            ->where('phone_e164', $phoneE164)
            ->where('id', '!=', $engagement->lead_id)
            ->exists();
        if ($exists) {
            return back()->withErrors(['phone' => 'This phone number is already registered to another lead.'])->withInput();
        }

        $engagement->lead->update([
            'name'       => $request->name,
            'email'      => $request->email,
            'phone_e164' => $phoneE164,
        ]);

        return back()->with('success', 'Lead details updated successfully.');
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

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Status updated successfully']);
        }
        return back()->with('success', 'Status updated successfully');
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
        $firstError = null;

        foreach ($data as $row) {
            // Ensure the row has the exact same number of columns as the headers
            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            } elseif (count($row) > count($headers)) {
                $row = array_slice($row, 0, count($headers));
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
                if (!$firstError) $firstError = "Row missing required name or phone column.";
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
                if (!$firstError) $firstError = "Failed on '" . $input['name'] . "': " . $e->getMessage();
            }
        }

        $msg = "Import complete: $success created, $duplicates duplicates, $failed failed.";
        if ($firstError) {
            $msg .= " (Example error: $firstError)";
        }

        return back()->with($failed > 0 ? 'error' : 'success', $msg);
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

        // Phase 11b: If a salesperson creates a lead manually and it's not explicitly unassigned by an admin,
        // they get ownership of it immediately.
        if ($user->role === 'salesperson' && empty($request->assigned_user_id)) {
            $assignedUserId = $user->id;
        }

        try {
            $result = $intakeService->intake([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'source' => $request->source,
                'assigned_user_id' => $assignedUserId,
            ], $companyId, $user->id);

            if ($result['is_duplicate']) {
                $dupEngagement = $result['engagement'];
                return back()
                    ->with('duplicate_engagement_id', $dupEngagement->id)
                    ->with('duplicate_lead_name', $dupEngagement->lead->name ?? 'Unknown')
                    ->with('duplicate_owner_name', $dupEngagement->assignedTo?->name ?? 'Unassigned')
                    ->with('duplicate_owner_team_id', $dupEngagement->assignedTo?->team_id)
                    ->withInput();
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
            'disposition_id' => 'nullable', // optional — not always available from quick-log
            'type'           => 'nullable|string|in:call,note,followup,visit',
            'notes'          => 'nullable|string',
            'follow_up_at'   => 'nullable|date',
        ]);

        $engagement = \App\Models\LeadEngagement::findOrFail($id);

        $activity = \App\Models\Activity::create([
            'engagement_id'      => $engagement->id,
            'created_by_user_id' => $user->id,
            'disposition_id'     => $request->filled('disposition_id') && is_numeric($request->disposition_id) ? $request->disposition_id : null,
            'type'               => $request->filled('type') ? $request->type : 'call',
            'notes'              => $request->notes,
            'follow_up_at'       => $request->follow_up_at,
        ]);

        // If follow_up_at is provided, engagement needs last_activity_at updated
        $engagement->update(['last_activity_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'activity' => $activity, 'message' => 'Activity logged successfully.']);
        }
        return back()->with('success', 'Activity logged successfully.');
    }
}

# HiLITE LMS — Developer C Implementation Plan
> **Role: Assignment Engine, Audit Log, SLA Breach, User Management, Tests, Git Discipline**
> Stack: Laravel 11 · MySQL 8 · Sanctum · Redis + Horizon
> Reference: API_CONTRACTS.md (read before writing a single line)
> Dependency: Dev A migrations must be done before Day 3

---

## Your Boundaries

**You own:**
- `AssignmentService` — the only place reassignment logic lives
- `AssignmentController`
- `AuditLogService` — called by Dev A and Dev B, you write the internals
- `AuditLogController`
- `UserController` (admin — list assignable users)
- `SlaPolicy` model + admin endpoints
- `CheckSlaBreachJob` — scheduled queue job that flags breached leads
- `MarkDormantLeadsJob` — scheduled job that marks cold leads dormant
- Role-based authorization (`RoleMiddleware` or Gate policies)
- PHPUnit feature tests for the full demo flow
- Git process: PR reviews, merge discipline, branch hygiene
- `DatabaseSeeder` orchestration (you call all seeders in correct order)

**You do NOT touch:**
- Lead creation or dedup — Dev A
- Stage transitions or activity creation — Dev B
- `LeadIntakeService` — Dev A
- `StageTransitionService` — Dev B
- Migrations — Dev A writes all migrations

---

## Day-by-Day Plan

### Day 1–2 — AuditLogService Stub + Role Gates

**Do this first** — Dev A and Dev B both import and call `AuditLogService`. The stub must exist from Day 1 so their code compiles.

**`app/Services/AuditLogService.php`** — write this on Day 1:
```php
<?php
namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    /**
     * Called by LeadIntakeService, StageTransitionService, AssignmentService, ActivityController.
     * Never throws — audit failure must never crash a request.
     */
    public function log(
        int $companyId,
        ?int $engagementId,
        ?int $actorUserId,
        string $action,
        mixed $before,
        mixed $after
    ): void {
        try {
            AuditLog::create([
                'company_id'    => $companyId,
                'engagement_id' => $engagementId,
                'actor_user_id' => $actorUserId,
                'action'        => $action,
                'before'        => $before ? json_encode($before) : null,
                'after'         => $after  ? json_encode($after)  : null,
            ]);
        } catch (\Throwable $e) {
            // Log to Laravel log, never re-throw
            \Log::error('AuditLog write failed: ' . $e->getMessage(), [
                'action'        => $action,
                'engagement_id' => $engagementId,
            ]);
        }
    }
}
```

**`app/Models/AuditLog.php`**
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'company_id','engagement_id','actor_user_id','action','before','after'
    ];

    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
    ];

    public function actor()      { return $this->belongsTo(User::class, 'actor_user_id'); }
    public function engagement() { return $this->belongsTo(LeadEngagement::class, 'engagement_id'); }
}
```

**Role-based Gates — `app/Providers/AppServiceProvider.php`**
```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    // Who can assign leads to anyone in the company
    Gate::define('assign-leads-globally', fn($user) =>
        in_array($user->role, ['admin','super_admin','manager'])
    );

    // Who can assign leads within their own team only
    Gate::define('assign-leads-in-team', fn($user) =>
        in_array($user->role, ['team_lead','admin','super_admin','manager'])
    );

    // Who can view audit logs
    Gate::define('view-audit-logs', fn($user) =>
        in_array($user->role, ['admin','super_admin','manager','branch_head'])
    );

    // Who can manage SLA policies
    Gate::define('manage-sla-policies', fn($user) =>
        in_array($user->role, ['admin','super_admin'])
    );
}
```

---

### Day 3 — AssignmentService + AssignmentController

**`app/Services/AssignmentService.php`**
```php
<?php
namespace App\Services;

use App\Models\LeadEngagement;
use App\Models\OwnershipAssignment;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Carbon;

class AssignmentService
{
    public function __construct(protected AuditLogService $auditService) {}

    /**
     * Assigns or reassigns a lead engagement to a user.
     * Enforces role-based scope:
     *   - salesperson: cannot assign at all
     *   - team_lead: can only assign within their own team
     *   - manager/admin/super_admin: can assign anyone in company
     *
     * @throws \Exception on authorization failure
     */
    public function assign(
        LeadEngagement $engagement,
        int $assignToUserId,
        int $actorUserId,
        ?string $reason = null
    ): OwnershipAssignment {
        $actor    = User::findOrFail($actorUserId);
        $assignTo = User::findOrFail($assignToUserId);

        // Validate actor permission scope
        $this->validateAssignmentPermission($actor, $assignTo, $engagement);

        $previousOwnerId = $engagement->assigned_user_id;

        // Update the engagement's current owner
        $engagement->update(['assigned_user_id' => $assignToUserId]);

        // Append to assignment history (never update, always insert)
        $assignment = OwnershipAssignment::create([
            'engagement_id'        => $engagement->id,
            'assigned_to_user_id'  => $assignToUserId,
            'assigned_by_user_id'  => $actorUserId,
            'reason'               => $reason,
            'assigned_at'          => Carbon::now(),
        ]);

        $this->auditService->log(
            companyId: $engagement->company_id,
            engagementId: $engagement->id,
            actorUserId: $actorUserId,
            action: $previousOwnerId ? 'reassigned' : 'assigned',
            before: $previousOwnerId ? ['assigned_user_id' => $previousOwnerId] : null,
            after:  ['assigned_user_id' => $assignToUserId, 'reason' => $reason]
        );

        return $assignment->load(['assignedTo','assignedBy']);
    }

    /**
     * Returns users the actor is permitted to assign leads to.
     */
    public function getAssignableUsers(User $actor): \Illuminate\Database\Eloquent\Collection
    {
        $query = User::where('company_id', $actor->company_id)
            ->where('is_active', true)
            ->where('role', 'salesperson');

        if (in_array($actor->role, ['admin','super_admin','manager','branch_head'])) {
            // Can assign to anyone in the company
            return $query->get();
        }

        if ($actor->role === 'team_lead') {
            // Can only assign within their own team
            return $query->where('team_id', $actor->team_id)->get();
        }

        // Salesperson — cannot assign
        return collect();
    }

    /**
     * Throws exception if actor cannot assign to the target user.
     */
    private function validateAssignmentPermission(User $actor, User $assignTo, LeadEngagement $engagement): void
    {
        if ($actor->role === 'salesperson') {
            throw new \Exception('Salespersons cannot reassign leads.');
        }

        if ($actor->role === 'team_lead') {
            if ($assignTo->team_id !== $actor->team_id) {
                throw new \Exception('You can only assign leads within your team.');
            }
            return;
        }

        // manager, branch_head, admin, super_admin — just check same company
        if ($assignTo->company_id !== $actor->company_id) {
            throw new \Exception('Cannot assign leads to a user from another company.');
        }
    }
}
```

**`app/Http/Controllers/Api/AssignmentController.php`**
```php
<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadEngagement;
use App\Models\User;
use App\Services\AssignmentService;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(protected AssignmentService $assignmentService) {}

    public function assign(Request $request, int $engagementId)
    {
        $request->validate([
            'assign_to_user_id' => 'required|integer|exists:users,id',
            'reason'            => 'nullable|string|max:500',
        ]);

        // Global scope applies — company-scoped automatically
        $engagement = LeadEngagement::findOrFail($engagementId);

        try {
            $assignment = $this->assignmentService->assign(
                engagement:      $engagement,
                assignToUserId:  $request->assign_to_user_id,
                actorUserId:     $request->user()->id,
                reason:          $request->reason,
            );
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => []], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'engagement_id' => $engagement->id,
                'assigned_to'   => ['id' => $assignment->assignedTo->id, 'name' => $assignment->assignedTo->name],
                'assigned_by'   => ['id' => $assignment->assignedBy->id, 'name' => $assignment->assignedBy->name],
                'reason'        => $assignment->reason,
                'assigned_at'   => $assignment->assigned_at->toIso8601String(),
            ],
            'message' => 'Lead assigned successfully',
        ]);
    }

    public function assignable(Request $request)
    {
        $actor = $request->user();
        $users = $this->assignmentService->getAssignableUsers($actor);

        return response()->json([
            'success' => true,
            'data'    => $users->map(fn($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'role'         => $u->role,
                'active_leads' => LeadEngagement::where('assigned_user_id', $u->id)
                    ->where('status', 'active')
                    ->count(),
            ]),
        ]);
    }
}
```

---

### Day 4 — Audit Log Endpoint + SLA Policies

**`app/Http/Controllers/Api/AuditLogController.php`**
```php
<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // Gate check — only managers and above
        if (!$request->user()->can('view-audit-logs')) {
            return response()->json(['success' => false, 'message' => 'Forbidden', 'errors' => []], 403);
        }

        $companyId = app('current_company_id');

        $logs = AuditLog::with(['actor','engagement.lead'])
            ->where('company_id', $companyId)
            ->when($request->engagement_id, fn($q) => $q->where('engagement_id', $request->engagement_id))
            ->when($request->user_id,       fn($q) => $q->where('actor_user_id', $request->user_id))
            ->when($request->action,        fn($q) => $q->where('action', $request->action))
            ->when($request->from_date,     fn($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date,       fn($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 25);

        return response()->json([
            'success' => true,
            'data'    => $logs->map(fn($l) => [
                'id'            => $l->id,
                'engagement_id' => $l->engagement_id,
                'action'        => $l->action,
                'actor'         => $l->actor ? ['id' => $l->actor->id, 'name' => $l->actor->name] : null,
                'before'        => $l->before,
                'after'         => $l->after,
                'created_at'    => $l->created_at->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ]
        ]);
    }
}
```

**`app/Http/Controllers/Api/Admin/SlaPolicyController.php`**
```php
<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SlaPolicy;
use Illuminate\Http\Request;

class SlaPolicyController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->can('manage-sla-policies')) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $companyId = app('current_company_id');
        $policies  = SlaPolicy::with('stage')
            ->where('company_id', $companyId)
            ->get()
            ->map(fn($p) => [
                'id'               => $p->id,
                'stage_id'         => $p->stage_id,
                'stage_name'       => $p->stage->name,
                'sla_days'         => $p->sla_days,
                'escalate_to_role' => $p->escalate_to_role,
                'company_id'       => $p->company_id,
            ]);

        return response()->json(['success' => true, 'data' => $policies]);
    }

    public function update(Request $request, int $id)
    {
        if (!$request->user()->can('manage-sla-policies')) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate([
            'sla_days'         => 'required|integer|min:1|max:365',
            'escalate_to_role' => 'nullable|in:team_lead,manager,admin',
        ]);

        $policy = SlaPolicy::where('company_id', app('current_company_id'))->findOrFail($id);
        $policy->update($request->only('sla_days','escalate_to_role'));

        return response()->json(['success' => true, 'data' => $policy, 'message' => 'SLA policy updated']);
    }
}
```

---

### Day 5 — SLA Breach Job + Dormant Lead Job

**`app/Jobs/CheckSlaBreachJob.php`** — runs on a schedule, flags overdue leads:
```php
<?php
namespace App\Jobs;

use App\Models\LeadEngagement;
use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;

class CheckSlaBreachJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        // Find all active engagements where sla_due_at has passed and not yet flagged
        LeadEngagement::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('sla_breached', false)
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', Carbon::now())
            ->chunkById(100, function ($engagements) {
                foreach ($engagements as $engagement) {
                    $engagement->update(['sla_breached' => true]);

                    AuditLog::create([
                        'company_id'    => $engagement->company_id,
                        'engagement_id' => $engagement->id,
                        'actor_user_id' => null, // system action
                        'action'        => 'sla_breached',
                        'before'        => null,
                        'after'         => json_encode(['sla_due_at' => $engagement->sla_due_at]),
                    ]);
                }
            });
    }
}
```

**`app/Jobs/MarkDormantLeadsJob.php`** — runs weekly, marks inactive leads dormant:
```php
<?php
namespace App\Jobs;

use App\Models\LeadEngagement;
use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;

class MarkDormantLeadsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    // Leads with no activity for this many days become dormant
    const DORMANT_AFTER_DAYS = 180;

    public function handle(): void
    {
        $cutoff = Carbon::now()->subDays(self::DORMANT_AFTER_DAYS);

        LeadEngagement::withoutGlobalScopes()
            ->where('status', 'active')
            ->where(fn($q) =>
                $q->where('last_activity_at', '<', $cutoff)
                  ->orWhereNull('last_activity_at')
            )
            ->chunkById(100, function ($engagements) {
                foreach ($engagements as $engagement) {
                    $engagement->update(['status' => 'dormant', 'dormant_at' => now()]);

                    AuditLog::create([
                        'company_id'    => $engagement->company_id,
                        'engagement_id' => $engagement->id,
                        'actor_user_id' => null,
                        'action'        => 'lead_dormant',
                        'before'        => json_encode(['status' => 'active']),
                        'after'         => json_encode(['status' => 'dormant']),
                    ]);
                }
            });
    }
}
```

**Register both jobs in `routes/console.php`:**
```php
use App\Jobs\CheckSlaBreachJob;
use App\Jobs\MarkDormantLeadsJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new CheckSlaBreachJob)->hourly();
Schedule::job(new MarkDormantLeadsJob)->weekly();
```

---

### Day 6 — User Management + Routes

**`app/Http/Controllers/Api/Admin/UserController.php`**
```php
<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LeadEngagement;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['admin','super_admin','manager','branch_head','team_lead'])) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $companyId = app('current_company_id');

        $users = User::where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->map(fn($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'email'        => $u->email,
                'role'         => $u->role,
                'branch_id'    => $u->branch_id,
                'team_id'      => $u->team_id,
                'active_leads' => LeadEngagement::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('assigned_user_id', $u->id)
                    ->where('status','active')
                    ->count(),
                'company_id'   => $u->company_id,
            ]);

        return response()->json(['success' => true, 'data' => $users]);
    }
}
```

**Add your routes to `routes/api.php`** (inside the auth middleware group):
```php
// Dev C routes — add inside middleware group
Route::patch('/engagements/{id}/assign', [AssignmentController::class, 'assign']);
Route::get('/users/assignable', [AssignmentController::class, 'assignable']);
Route::get('/audit-logs', [AuditLogController::class, 'index']);
Route::get('/admin/sla-policies', [SlaPolicyController::class, 'index']);
Route::patch('/admin/sla-policies/{id}', [SlaPolicyController::class, 'update']);
Route::get('/admin/users', [UserController::class, 'index']);
```

---

### Day 7 — Full Test Suite

**`tests/Feature/AssignmentTest.php`**
- TL can assign a lead to a user in their own team
- TL cannot assign a lead to a user in a different team → 403
- Manager can assign a lead to anyone in the company
- Salesperson cannot assign → 403
- Assignment is recorded in ownership_assignments table
- Assignment logs an audit entry
- Reassignment updates assigned_user_id on engagement
- User from Company A cannot assign a Company B engagement

**`tests/Feature/AuditLogTest.php`**
- Manager can GET /audit-logs
- Salesperson gets 403 on GET /audit-logs
- Audit log only returns current company's entries
- lead_created action logged when lead is created
- stage_changed action logged when stage is moved
- assigned action logged when lead is assigned

**`tests/Feature/SlaBreachTest.php`**
- CheckSlaBreachJob marks engagement as sla_breached when past due
- CheckSlaBreachJob does not re-flag already breached engagements
- MarkDormantLeadsJob marks engagement dormant after 180 days no activity
- Dormant engagement is excluded from default active leads list

**`tests/Feature/FullDemoFlowTest.php`** — the most important test:
```
1. Login as Company A salesperson → get token
2. Create a new lead → 201, is_duplicate false
3. Create same lead again → 200, is_duplicate true, conflict true (not yet assigned)
4. Login as Company A TL → assign lead to salesperson
5. Login as salesperson → move stage from New to Contacted
6. Add a follow-up activity
7. Login as Company B salesperson → try to GET the Company A engagement → 404
8. Run CheckSlaBreachJob with a past sla_due_at → engagement marked breached
9. GET /audit-logs as manager → all 4 actions present
```

Run with: `php artisan test --filter FullDemoFlowTest`

---

### Day 7–8 — Git Discipline (your ongoing responsibility)

**You are the Git process owner. Enforce these rules every day:**

Branch structure:
```
production   ← tagged releases only, always deployable
develop      ← integration branch, CI runs here
feature/*    ← one per task, short-lived
hotfix/*     ← from production only, merged back to both
```

Daily rules:
```bash
# Before starting work
git checkout develop && git pull origin develop
git checkout -b feature/your-task-name

# Commit often with clear messages
git commit -m "feat: add SLA breach detection job"
git commit -m "test: add full demo flow integration test"
git commit -m "fix: correct company scope on assignable users query"

# End of day — push and open PR
git push origin feature/your-task-name
# Open PR on GitHub → base: develop
```

PR checklist — paste this into every PR description:
```markdown
## What this does
[one sentence]

## Schema impact
[ ] No migrations
[ ] New migration: [table/column name]

## Tenant scoping
[ ] All queries are company-scoped or use withoutGlobalScopes() intentionally

## How to test
1. [step]
2. [step]

## Verified
[ ] php artisan test passes
[ ] Company A cannot see Company B data
[ ] No raw SQL bypassing Eloquent
[ ] Migration has a down() method
```

Review rules:
- Minimum 1 teammate approval before merge — no self-merges ever
- Squash merge into develop — keeps history clean
- Regular merge commit from develop → production at release
- Code freeze after Day 8 — only bug fixes and demo-hardening

**What to look for in every review:**
- Does every LeadEngagement query go through the global company scope?
- Does any new endpoint bypass the `company.scope` middleware?
- Does the response shape match API_CONTRACTS.md exactly?
- Are there any places where a user could access another company's data?

---

## Seeders (Your Responsibility to Orchestrate)

`database/seeders/DatabaseSeeder.php` — you own this file:
```php
public function run(): void
{
    $this->call([
        CompanySeeder::class,       // Dev A writes this
        BranchTeamSeeder::class,    // You write this
        UserSeeder::class,          // Dev A writes this
        SystemUserSeeder::class,    // You write this — NEW
        PipelineStageSeeder::class, // Dev A writes this
        DispositionSeeder::class,   // You write this
        SlaPolicySeeder::class,     // You write this
        LeadSeeder::class,          // Dev A writes this
    ]);
}
```

`BranchTeamSeeder`: Create 2 branches + 2 teams per company. Assign TLs and salespersons to teams.

`SystemUserSeeder` — **NEW (added during hardening pass):** `users.email`
has a global `unique()` constraint (see Dev A's migration), so a single
`system@internal.local` cannot be reused across companies. For each company,
create one user with `email = 'system+' . $company->slug . '@internal.local'`
(e.g. `system+hilite-builders@internal.local`), `role = 'salesperson'`,
`is_active = false`, `company_id = $company->id`. This user is never used for
login — Sanctum tokens are never issued for it. It exists solely so
`ProcessWebhookStagingJob` (Dev A) has a valid `actor_user_id` to attach to
`audit_logs` and `lead_engagements` created by webhook-sourced leads, since
those have no human actor. Confirm `getAssignableUsers()` filters on
`is_active = true` so this user never appears in assignment dropdowns.

`DispositionSeeder`: Seed the dispositions from API_CONTRACTS.md for each company.

`SlaPolicySeeder`: Seed default SLA policies (New=2d, Contacted=5d, Interested=7d, Site Visit=3d, Negotiation=10d) for both companies.

Verify everything works from scratch:
```bash
php artisan migrate:fresh --seed
```
This must complete without errors. Run it before every PR into develop.

---

## Do Not Do These (Dev A / Dev B Territory)
- Do not write lead creation logic
- Do not write LeadIntakeService
- Do not write StageTransitionService
- Do not write ActivityController
- Do not write migrations
- Do not touch React files

# HiLITE LMS — Developer A Implementation Plan
> **Role: Core Backend — Identity Layer, Deduplication, Intake Pipeline, Auth, Migrations**
> Stack: Laravel 11 · MySQL 8 · Sanctum · Redis + Horizon · giggsey/libphonenumber-for-php
> Reference: API_CONTRACTS.md (read before writing a single line)

---

## Your Boundaries

**You own:**
- All database migrations (every table — you are the schema owner)
- `companies`, `users`, `leads`, `lead_engagements`, `staging_leads`, `import_jobs` models
- `LeadIntakeService` — the single entry point for ALL lead creation regardless of source
- `PhoneNormalizationService`
- Auth endpoints (login, logout, me)
- Lead CRUD endpoints (POST /leads, GET /leads, GET /leads/{id}, GET /leads/check-duplicate)
- CSV upload endpoint + import job status endpoint
- Webhook intake endpoint
- Queue jobs: `ProcessImportRowJob`, `ProcessWebhookStagingJob`
- `EnforceCompanyScope` middleware
- Global Eloquent scope on `LeadEngagement`
- Database seeders for companies, users, pipeline stages

**You do NOT touch:**
- `AssignmentService` — Dev C owns this
- `AuditLogService` — Dev C owns this, but you CALL it from your services
- Activities, dispositions, SLA policies — Dev B and Dev C
- Any React frontend files

---

## Day-by-Day Plan

### Day 1 — Project Setup + Schema
**Goal: Schema frozen, migrations run clean, repo ready for all three devs.**

**Step 1: Create the Laravel project**
```bash
composer create-project laravel/laravel hilite-lms
cd hilite-lms
composer require laravel/sanctum
composer require giggsey/libphonenumber-for-php
composer require laravel/horizon
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"
php artisan install:api
```

**Step 2: Configure .env**
```env
APP_NAME=HiLITE-LMS
APP_ENV=local
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hilite_lms
DB_USERNAME=root
DB_PASSWORD=
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
WEBHOOK_SECRET_KEY=hilite_webhook_secret_change_in_prod
```

**Step 3: Generate all models + migrations at once**
```bash
php artisan make:model Company -m
php artisan make:model Lead -m
php artisan make:model LeadEngagement -m
php artisan make:model PipelineStage -m
php artisan make:model Disposition -m
php artisan make:model Activity -m
php artisan make:model OwnershipAssignment -m
php artisan make:model AuditLog -m
php artisan make:model StagingLead -m
php artisan make:model ImportJob -m
php artisan make:model SlaPolicy -m
# User model already exists — just edit it
```

**Step 4: Write all migrations in this exact order**

`create_companies_table`:
```php
Schema::create('companies', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique(); // hilite-builders, hilite-properties
    $table->string('webhook_key')->unique()->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

`modify_users_table` (edit the existing users migration):
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->enum('role', ['super_admin','admin','manager','branch_head','team_lead','salesperson']);
    $table->boolean('is_active')->default(true);
    $table->rememberToken();
    $table->timestamps();
    $table->index(['company_id', 'role']);
});
```

> Note: `branches` and `teams` tables are simple: id, name, company_id, timestamps. Create migrations for them too. They have no complex logic — Dev C seeds them.

`create_leads_table` — **this is the global identity table, NO company_id here**:
```php
Schema::create('leads', function (Blueprint $table) {
    $table->id();
    $table->string('phone_e164', 20)->unique(); // UNIQUE — this is the dedup key
    $table->string('name');
    $table->string('email')->nullable();
    $table->enum('status', ['active','dormant','closed'])->default('active');
    $table->timestamp('dormant_at')->nullable();
    $table->timestamps();
    $table->index('phone_e164');
    $table->index('status');
});
```

`create_pipeline_stages_table`:
```php
Schema::create('pipeline_stages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->integer('order')->default(0);
    $table->string('color', 10)->default('#6366f1');
    $table->integer('sla_days')->nullable();
    $table->boolean('is_closed')->default(false);
    $table->timestamps();
    $table->index(['company_id', 'order']);
});
```

`create_dispositions_table`:
```php
Schema::create('dispositions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('stage_id')->nullable()->constrained('pipeline_stages')->nullOnDelete();
    $table->string('label');
    $table->timestamps();
});
```

`create_lead_engagements_table` — **company-scoped relationship record**:
```php
Schema::create('lead_engagements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
    $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('stage_id')->constrained('pipeline_stages');
    $table->enum('source', ['manual','csv','webhook','callsync_auto'])->default('manual');
    $table->enum('status', ['active','dormant','closed'])->default('active');
    $table->timestamp('last_activity_at')->nullable();
    $table->timestamp('sla_due_at')->nullable();
    $table->boolean('sla_breached')->default(false);
    $table->timestamps();
    // One company can only have ONE engagement per lead
    $table->unique(['company_id', 'lead_id']);
    $table->index(['company_id', 'stage_id']);
    $table->index(['company_id', 'assigned_user_id']);
    $table->index(['company_id', 'status']);
    $table->index('sla_due_at');
});
```

`create_activities_table`:
```php
Schema::create('activities', function (Blueprint $table) {
    $table->id();
    $table->foreignId('engagement_id')->constrained('lead_engagements')->cascadeOnDelete();
    $table->foreignId('created_by_user_id')->constrained('users');
    $table->foreignId('disposition_id')->nullable()->constrained('dispositions')->nullOnDelete();
    $table->enum('type', ['note','followup','call','visit']);
    $table->text('notes')->nullable();
    $table->timestamp('follow_up_at')->nullable();
    $table->timestamps();
    $table->index(['engagement_id', 'type']);
    $table->index('follow_up_at');
});
```

`create_ownership_assignments_table`:
```php
Schema::create('ownership_assignments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('engagement_id')->constrained('lead_engagements')->cascadeOnDelete();
    $table->foreignId('assigned_to_user_id')->constrained('users');
    $table->foreignId('assigned_by_user_id')->constrained('users');
    $table->string('reason')->nullable();
    $table->timestamp('assigned_at');
    $table->timestamps();
    $table->index('engagement_id');
});
```

`create_audit_logs_table`:
```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('engagement_id')->nullable()->constrained('lead_engagements')->nullOnDelete();
    $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('action', 50); // lead_created, duplicate_attached, stage_changed, etc.
    $table->json('before')->nullable();
    $table->json('after')->nullable();
    $table->timestamps();
    $table->index(['company_id', 'action']);
    $table->index('engagement_id');
});
```

`create_staging_leads_table` — **append-only buffer for queue**:
```php
Schema::create('staging_leads', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('import_job_id')->nullable()->constrained('import_jobs')->nullOnDelete();
    $table->string('raw_phone');
    $table->string('raw_name');
    $table->string('raw_email')->nullable();
    $table->string('source')->default('manual');
    $table->text('raw_notes')->nullable();
    $table->json('meta')->nullable(); // webhook campaign data etc
    $table->enum('status', ['pending','processing','done','failed'])->default('pending');
    $table->string('failure_reason')->nullable();
    $table->string('idempotency_key', 64)->unique(); // prevents double-queueing
    $table->timestamps();
    $table->index(['status', 'company_id']);
});
```

`create_import_jobs_table`:
```php
Schema::create('import_jobs', function (Blueprint $table) {
    $table->id();
    $table->string('uuid')->unique();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('uploaded_by_user_id')->constrained('users');
    $table->string('original_filename');
    $table->enum('status', ['queued','processing','done','failed'])->default('queued');
    $table->integer('total_rows')->default(0);
    $table->integer('processed_rows')->default(0);
    $table->integer('created_count')->default(0);
    $table->integer('duplicate_count')->default(0);
    $table->integer('failed_count')->default(0);
    $table->json('error_log')->nullable(); // [{row: 12, reason: "..."}]
    $table->timestamps();
});
```

`create_sla_policies_table`:
```php
Schema::create('sla_policies', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('stage_id')->constrained('pipeline_stages')->cascadeOnDelete();
    $table->integer('sla_days');
    $table->enum('escalate_to_role', ['team_lead','manager','admin'])->default('team_lead');
    $table->timestamps();
    $table->unique(['company_id', 'stage_id']);
});
```

**Step 5: Run migrations**
```bash
php artisan migrate
```

---

### Day 2 — Auth + Phone Normalization + Company Scope

**`app/Services/PhoneNormalizationService.php`**
```php
<?php
namespace App\Services;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

class PhoneNormalizationService
{
    protected PhoneNumberUtil $util;

    public function __construct()
    {
        $this->util = PhoneNumberUtil::getInstance();
    }

    /**
     * Normalize any phone string to E.164.
     * Tries IN first, then AE. Returns null if unparseable.
     */
    public function normalize(string $raw): ?string
    {
        $raw = trim($raw);
        foreach (['IN', 'AE'] as $region) {
            try {
                $parsed = $this->util->parse($raw, $region);
                if ($this->util->isValidNumber($parsed)) {
                    return $this->util->format($parsed, PhoneNumberFormat::E164);
                }
            } catch (NumberParseException $e) {
                continue;
            }
        }
        return null; // caller must handle null as invalid phone
    }

    public function isValid(string $raw): bool
    {
        return $this->normalize($raw) !== null;
    }
}
```

**`app/Http/Middleware/EnforceCompanyScope.php`**
```php
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
```

Register in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'company.scope' => \App\Http\Middleware\EnforceCompanyScope::class,
    ]);
})
```

**`app/Models/LeadEngagement.php`** — global scope is critical:
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class LeadEngagement extends Model
{
    protected $fillable = [
        'company_id','lead_id','assigned_user_id','stage_id',
        'source','status','last_activity_at','sla_due_at','sla_breached'
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'sla_breached' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Automatically scope every query to current company
        // Use withoutGlobalScopes() ONLY in LeadIntakeService for dedup check
        static::addGlobalScope('company', function (Builder $builder) {
            if (app()->bound('current_company_id')) {
                $builder->where('lead_engagements.company_id', app('current_company_id'));
            }
        });
    }

    public function lead()        { return $this->belongsTo(Lead::class); }
    public function stage()       { return $this->belongsTo(PipelineStage::class); }
    public function assignedTo()  { return $this->belongsTo(User::class, 'assigned_user_id'); }
    public function activities()  { return $this->hasMany(Activity::class, 'engagement_id'); }
    public function assignments() { return $this->hasMany(OwnershipAssignment::class, 'engagement_id'); }
    public function company()     { return $this->belongsTo(Company::class); }
}
```

**Auth Controller `app/Http/Controllers/Api/AuthController.php`**
```php
<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email','password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'errors'  => []
            ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user'  => [
                    'id'           => $user->id,
                    'name'         => $user->name,
                    'email'        => $user->email,
                    'role'         => $user->role,
                    'company_id'   => $user->company_id,
                    'company_name' => $user->company->name,
                    'branch_id'    => $user->branch_id,
                    'team_id'      => $user->team_id,
                ]
            ],
            'message' => 'Login successful'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'data' => [], 'message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('company');
        return response()->json(['success' => true, 'data' => [
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'role'         => $user->role,
            'company_id'   => $user->company_id,
            'company_name' => $user->company->name,
            'branch_id'    => $user->branch_id,
            'team_id'      => $user->team_id,
        ]]);
    }
}
```

---

### Day 3 — LeadIntakeService + Deduplication

**`app/Services/LeadIntakeService.php`** — the most important file in the project:
```php
<?php
namespace App\Services;

use App\Models\Lead;
use App\Models\LeadEngagement;
use App\Models\PipelineStage;
use App\Services\PhoneNormalizationService;
use App\Services\AuditLogService;

class LeadIntakeService
{
    public function __construct(
        protected PhoneNormalizationService $phoneService,
        protected AuditLogService $auditService,
    ) {}

    /**
     * Main entry point — called by manual form, CSV queue job, webhook queue job.
     * Returns ['engagement' => LeadEngagement, 'is_duplicate' => bool, 'conflict' => bool]
     */
    public function intake(array $data, int $companyId, int $actorUserId): array
    {
        $phone = $this->phoneService->normalize($data['phone'] ?? '');

        if (!$phone) {
            throw new \InvalidArgumentException('Invalid phone number: ' . ($data['phone'] ?? ''));
        }

        // Step 1: Find or create the GLOBAL master lead (no company scope here)
        // SECURITY/STABILITY: firstOrCreate is not atomic under concurrency.
        // Two requests for a brand-new phone can both pass the SELECT and both
        // attempt the INSERT; the unique constraint on phone_e164 makes the
        // second INSERT throw a 1062 duplicate-entry error. Catch it and
        // re-fetch instead of letting it bubble into a 500.
        try {
            $lead = Lead::firstOrCreate(
                ['phone_e164' => $phone],
                [
                    'name'   => $data['name'],
                    'email'  => $data['email'] ?? null,
                    'status' => 'active',
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int) $e->getCode() === 23000) { // SQLSTATE 23000 = integrity constraint violation
                $lead = Lead::where('phone_e164', $phone)->first();
                if (!$lead) {
                    throw $e; // genuinely unexpected — rethrow
                }
            } else {
                throw $e;
            }
        }

        // Step 2: Check if this company already has an engagement for this lead
        // MUST use withoutGlobalScopes here — we are checking cross-scope intentionally
        $existing = LeadEngagement::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('lead_id', $lead->id)
            ->first();

        if ($existing) {
            // Duplicate — engagement exists for this company
            $conflict = $existing->assigned_user_id
                && $existing->assigned_user_id !== $actorUserId
                && $existing->status === 'active';

            $this->auditService->log(
                companyId: $companyId,
                engagementId: $existing->id,
                actorUserId: $actorUserId,
                action: 'duplicate_attached',
                before: null,
                after: ['phone' => $phone]
            );

            // SECURITY: if the engagement is unassigned, the requesting exec
            // gets a generic "duplicate, unassigned" result — NOT the full
            // engagement payload (no stage/activity history of a record they
            // don't own yet). The controller decides what to expose; this
            // service just signals ownership state via 'conflict' and lets
            // the existing assignedTo relation be null-safe.
            return [
                'engagement'   => $existing->load(['lead','stage','assignedTo']),
                'is_duplicate' => true,
                'conflict'     => $conflict,
            ];
        }

        // Step 3: Create new engagement for this company
        $defaultStage = PipelineStage::where('company_id', $companyId)
            ->orderBy('order')
            ->first();

        $engagement = LeadEngagement::create([
            'company_id'       => $companyId,
            'lead_id'          => $lead->id,
            'assigned_user_id' => null,
            'stage_id'         => $defaultStage->id,
            'source'           => $data['source'] ?? 'manual',
            'status'           => 'active',
        ]);

        $this->auditService->log(
            companyId: $companyId,
            engagementId: $engagement->id,
            actorUserId: $actorUserId,
            action: 'lead_created',
            before: null,
            after: ['phone' => $phone, 'name' => $lead->name, 'source' => $engagement->source]
        );

        return [
            'engagement'   => $engagement->load(['lead','stage','assignedTo']),
            'is_duplicate' => false,
            'conflict'     => false,
        ];
    }
}
```

> **Note:** the original plan included a `queueFromStaging()` helper on
> this service that individual callers (CSV row loop, webhook) would call
> once per row. That method has been removed — both the CSV import
> controller (Day 5) and the webhook controller (Day 5) now bulk-insert
> directly into `staging_leads` via `DB::table()->insertOrIgnore()`. Calling
> through Eloquent per-row was the synchronous-loop problem described in the
> stability fixes below; bulk insert avoids instantiating one model per row.

---

### Day 4 — Lead Controller + Routes

**`app/Http/Controllers/Api/LeadController.php`**
```php
<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Services\LeadIntakeService;
use App\Services\PhoneNormalizationService;
use App\Models\LeadEngagement;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function __construct(
        protected LeadIntakeService $intakeService,
        protected PhoneNormalizationService $phoneService,
    ) {}

    public function index(Request $request)
    {
        $companyId = app('current_company_id');
        $actor     = $request->user();

        // SECURITY: row-level visibility by role.
        // salesperson  -> only own leads (assigned_user_id = self) or unassigned
        // team_lead    -> own + everyone on their team
        // branch_head+ -> everyone in the company (global scope already limits to company)
        $visibilityRoles = ['manager','branch_head','admin','super_admin'];

        // SECURITY: search must not LIKE-scan phone_e164. Exact match only on phone.
        // Free-text search is limited to name (prefix) and email (exact).
        $rawSearch = trim((string) $request->search);
        $searchPhone = null;
        if ($rawSearch !== '') {
            $normalized = $this->phoneService->normalize($rawSearch);
            if ($normalized) {
                $searchPhone = $normalized; // looks like a phone -> exact match path
            }
        }

        $query = LeadEngagement::with(['lead','stage','assignedTo'])
            ->when($searchPhone, fn($q) =>
                $q->whereHas('lead', fn($lq) => $lq->where('phone_e164', $searchPhone))
            )
            ->when(!$searchPhone && $rawSearch !== '', fn($q) =>
                $q->whereHas('lead', fn($lq) =>
                    $lq->where('name', 'like', $rawSearch.'%') // prefix match — can use index
                       ->orWhere('email', $rawSearch)          // exact match only
                )
            )
            ->when($request->stage_id, fn($q) => $q->where('stage_id', $request->stage_id))
            ->when($request->source, fn($q) => $q->where('source', $request->source))
            ->when($request->status, fn($q) => $q->where('status', $request->status));

        // assigned_to filter: salesperson cannot override to view others
        if ($actor->role === 'salesperson') {
            $query->where(fn($q) =>
                $q->where('assigned_user_id', $actor->id)
                  ->orWhereNull('assigned_user_id')
            );
        } elseif ($actor->role === 'team_lead') {
            $teamUserIds = \App\Models\User::where('company_id', $companyId)
                ->where('team_id', $actor->team_id)
                ->pluck('id');
            $query->where(fn($q) =>
                $q->whereIn('assigned_user_id', $teamUserIds)
                  ->orWhereNull('assigned_user_id')
            );
            if ($request->assigned_to) {
                $query->where('assigned_user_id', $request->assigned_to);
            }
        } elseif (in_array($actor->role, $visibilityRoles)) {
            $query->when($request->assigned_to, fn($q) => $q->where('assigned_user_id', $request->assigned_to));
        }

        // SECURITY: never-touched leads (last_activity_at = NULL) sort to the bottom, not the top
        $query->orderByRaw('last_activity_at IS NULL, last_activity_at DESC');

        // SECURITY: hard cap on per_page regardless of client input
        $perPage = min((int) ($request->per_page ?? 25), 100);
        $results = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $results->map(fn($e) => $this->formatEngagementSummary($e)),
            'meta'    => [
                'current_page' => $results->currentPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
            ]
        ]);
    }

    public function store(StoreLeadRequest $request)
    {
        $companyId   = app('current_company_id');
        $actor       = $request->user();
        $actorUserId = $actor->id;

        try {
            $result = $this->intakeService->intake($request->validated(), $companyId, $actorUserId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => []], 422);
        }

        $engagement = $result['engagement'];

        if ($result['conflict']) {
            return response()->json([
                'success' => false,
                'message' => 'This lead is already assigned to ' . optional($engagement->assignedTo)->name . '. Contact your Team Lead to reassign.',
                'errors'  => []
            ], 409);
        }

        // SECURITY: if it's a duplicate but NOT a conflict (unassigned, or
        // assigned to the requesting user themselves), only reveal the
        // assignee object when it IS the requesting user. Never hand a
        // plain salesperson someone else's name through the create endpoint —
        // the 409/conflict branch above is the only path that should ever
        // do that, and only because it's an actionable "go talk to them" message.
        $assignedToPayload = null;
        if ($engagement->assignedTo && $engagement->assignedTo->id === $actorUserId) {
            $assignedToPayload = ['id' => $engagement->assignedTo->id, 'name' => $engagement->assignedTo->name];
        }

        $status = $result['is_duplicate'] ? 200 : 201;
        return response()->json([
            'success' => true,
            'data'    => [
                'lead_id'       => $engagement->lead_id,
                'engagement_id' => $engagement->id,
                'phone_e164'    => $engagement->lead->phone_e164,
                'is_duplicate'  => $result['is_duplicate'],
                'assigned_to'   => $assignedToPayload,
                'stage'         => $engagement->stage->name,
            ],
            'message' => $result['is_duplicate'] ? 'Duplicate lead attached' : 'Lead created',
        ], $status);
    }

    public function show(Request $request, int $id)
    {
        $engagement = LeadEngagement::with([
            'lead','stage','assignedTo',
            'activities.createdBy','activities.disposition',
            'assignments.assignedTo','assignments.assignedBy'
        ])->findOrFail($id); // global company scope already applies

        // SECURITY: ownership check — a salesperson cannot open another
        // salesperson's assigned engagement just by guessing the id.
        $actor = $request->user();
        $privileged = ['manager','branch_head','admin','super_admin'];

        if ($actor->role === 'salesperson') {
            $isOwner = $engagement->assigned_user_id === $actor->id;
            $isUnassigned = $engagement->assigned_user_id === null;
            if (!$isOwner && !$isUnassigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'This lead is assigned to another team member.',
                    'errors'  => []
                ], 403);
            }
        } elseif ($actor->role === 'team_lead') {
            $isOwner = $engagement->assigned_user_id === $actor->id;
            $isUnassigned = $engagement->assigned_user_id === null;
            $isOwnTeam = $engagement->assignedTo && $engagement->assignedTo->team_id === $actor->team_id;
            if (!$isOwner && !$isUnassigned && !$isOwnTeam) {
                return response()->json([
                    'success' => false,
                    'message' => 'This lead belongs to another team.',
                    'errors'  => []
                ], 403);
            }
        }
        // manager/branch_head/admin/super_admin -> company scope is sufficient

        return response()->json(['success' => true, 'data' => $this->formatEngagementDetail($engagement)]);
    }

    public function checkDuplicate(Request $request)
    {
        $request->validate(['phone' => 'required|string']);
        $companyId = app('current_company_id');
        $actor     = $request->user();
        $phone     = $this->phoneService->normalize($request->phone);

        if (!$phone) {
            return response()->json(['success' => true, 'data' => ['exists' => false, 'engagement_exists_in_company' => false]]);
        }

        $lead = \App\Models\Lead::where('phone_e164', $phone)->first();
        if (!$lead) {
            return response()->json(['success' => true, 'data' => ['exists' => false, 'engagement_exists_in_company' => false]]);
        }

        $engagement = LeadEngagement::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('lead_id', $lead->id)
            ->with(['assignedTo','stage'])
            ->first();

        // SECURITY: do not reveal another exec's name/stage to a plain salesperson
        // for a lead that is unassigned or assigned to someone else. Only
        // privileged roles or the assignee themselves see who owns it.
        $privileged = ['manager','branch_head','admin','super_admin','team_lead'];
        $canSeeOwner = $engagement
            && ($engagement->assigned_user_id === $actor->id || in_array($actor->role, $privileged));

        return response()->json(['success' => true, 'data' => [
            'exists'                       => true,
            'engagement_exists_in_company' => (bool) $engagement,
            'assigned_to'                  => $canSeeOwner && $engagement->assignedTo
                ? ['id' => $engagement->assignedTo->id, 'name' => $engagement->assignedTo->name]
                : null,
            'stage'                        => $canSeeOwner ? $engagement?->stage?->name : null,
            'is_assigned'                  => $engagement ? (bool) $engagement->assigned_user_id : false,
        ]]);
    }

    private function formatEngagementSummary(LeadEngagement $e): array
    {
        return [
            'engagement_id'    => $e->id,
            'lead_id'          => $e->lead_id,
            'name'             => $e->lead->name,
            'phone_e164'       => $e->lead->phone_e164,
            'email'            => $e->lead->email,
            'source'           => $e->source,
            'stage'            => ['id' => $e->stage->id, 'name' => $e->stage->name, 'color' => $e->stage->color],
            'assigned_to'      => $e->assignedTo ? ['id' => $e->assignedTo->id, 'name' => $e->assignedTo->name] : null,
            'last_activity_at' => $e->last_activity_at?->toIso8601String(),
            'sla_due_at'       => $e->sla_due_at?->toIso8601String(),
            'sla_breached'     => $e->sla_breached,
            'created_at'       => $e->created_at->toIso8601String(),
        ];
    }

    private function formatEngagementDetail(LeadEngagement $e): array
    {
        $summary = $this->formatEngagementSummary($e);
        $summary['activities'] = $e->activities->map(fn($a) => [
            'id'          => $a->id,
            'type'        => $a->type,
            'disposition' => $a->disposition ? ['id' => $a->disposition->id, 'label' => $a->disposition->label] : null,
            'notes'       => $a->notes,
            'follow_up_at'=> $a->follow_up_at?->toIso8601String(),
            'created_by'  => ['id' => $a->createdBy->id, 'name' => $a->createdBy->name],
            'created_at'  => $a->created_at->toIso8601String(),
        ])->toArray();
        $summary['assignment_history'] = $e->assignments->map(fn($a) => [
            'assigned_to' => ['id' => $a->assignedTo->id, 'name' => $a->assignedTo->name],
            'assigned_by' => ['id' => $a->assignedBy->id, 'name' => $a->assignedBy->name],
            'reason'      => $a->reason,
            'assigned_at' => $a->assigned_at->toIso8601String(),
        ])->toArray();
        return $summary;
    }
}
```

**`app/Http/Requests/StoreLeadRequest.php`**
```php
<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules(): array
    {
        return [
            'name'   => 'required|string|max:255',
            'phone'  => 'required|string|max:20',
            'email'  => 'nullable|email|max:255',
            'source' => 'nullable|in:manual,csv,webhook',
            'notes'  => 'nullable|string|max:2000',
        ];
    }
}
```

---

### Day 5 — CSV Import + Webhook + Queue Jobs

**`app/Http/Controllers/Api/ImportController.php`**

> **STABILITY FIX:** the original version read the whole CSV into a PHP
> array with `file()` + `array_map('str_getcsv', ...)` and then called
> `queueFromStaging()` once per row synchronously — for a 50k-row file this
> blocks the HTTP request for minutes and will hit `max_execution_time`.
> This version streams the file line-by-line with `SplFileObject` and bulk
> inserts staging rows in batches of 500 via `DB::table()->insert()`,
> bypassing Eloquent events entirely for the hot path. The request returns
> 202 almost immediately regardless of file size.

```php
<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportJob;
use App\Jobs\ProcessImportRowJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    const BATCH_SIZE = 500;
    const MAX_ROWS   = 50000; // hard cap — reject larger files outright

    public function upload(Request $request)
    {
        $request->validate([
            'file'   => 'required|file|mimes:csv,txt|max:10240', // 10MB
            'source' => 'nullable|string|max:100',
        ]);

        $companyId = app('current_company_id');
        $userId    = $request->user()->id;
        $file      = $request->file('file');
        $source    = $request->source ?? 'csv';

        $handle = new \SplFileObject($file->getRealPath(), 'r');
        $handle->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

        $headerRow = $handle->fgetcsv();
        if (!$headerRow) {
            return response()->json(['success' => false, 'message' => 'CSV file is empty', 'errors' => []], 422);
        }
        $headers = array_map(fn($h) => strtolower(trim($h)), $headerRow);

        $job = ImportJob::create([
            'uuid'                => Str::uuid(),
            'company_id'          => $companyId,
            'uploaded_by_user_id' => $userId,
            'original_filename'   => $file->getClientOriginalName(),
            'status'              => 'queued',
            'total_rows'          => 0, // filled in below as we count
        ]);

        $batch = [];
        $totalRows = 0;
        $now = now();

        while (!$handle->eof()) {
            $row = $handle->fgetcsv();
            if ($row === null || $row === [null] || $row === false) {
                continue;
            }

            $totalRows++;

            // STABILITY: hard cap — stop accepting rows past MAX_ROWS,
            // mark the job partial, and tell the user to split the file.
            if ($totalRows > self::MAX_ROWS) {
                $job->update([
                    'status'     => 'failed',
                    'error_log'  => [['row' => self::MAX_ROWS + 1, 'reason' => 'File exceeds max ' . self::MAX_ROWS . ' rows. Please split into smaller files.']],
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'File exceeds the maximum of ' . self::MAX_ROWS . ' rows. Please split into smaller files.',
                    'errors'  => []
                ], 422);
            }

            $data = array_combine($headers, array_pad($row, count($headers), null));

            $rawPhone = trim((string) ($data['phone'] ?? ''));
            $rawName  = trim((string) ($data['name'] ?? ''));

            // Rows failing basic shape checks are recorded as failed immediately —
            // never queued, never touch LeadIntakeService.
            if ($rawPhone === '' || $rawName === '') {
                $job->increment('failed_count');
                $errors   = $job->error_log ?? [];
                $errors[] = ['row' => $totalRows, 'reason' => 'Missing name or phone'];
                $job->update(['error_log' => $errors]);
                continue;
            }

            $idempotencyKey = hash('sha256', $companyId . '|' . strtolower($rawPhone) . '|' . $source . '|' . $job->id);

            $batch[] = [
                'company_id'      => $companyId,
                'import_job_id'   => $job->id,
                'raw_phone'       => $rawPhone,
                'raw_name'        => $rawName,
                'raw_email'       => trim((string) ($data['email'] ?? '')) ?: null,
                'source'          => $source,
                'raw_notes'       => trim((string) ($data['notes'] ?? '')) ?: null,
                'meta'            => null,
                'status'          => 'pending',
                'idempotency_key' => $idempotencyKey,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];

            // STABILITY: bulk-insert in batches of 500 — avoids one giant
            // INSERT and avoids 50k individual Eloquent model instantiations.
            if (count($batch) >= self::BATCH_SIZE) {
                DB::table('staging_leads')->insertOrIgnore($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('staging_leads')->insertOrIgnore($batch);
        }

        $job->update(['total_rows' => $totalRows]);

        // STABILITY: dispatch processes in chunks (see ProcessImportRowJob below),
        // not one mega-job looping over all rows synchronously.
        ProcessImportRowJob::dispatch($job->id);

        return response()->json([
            'success' => true,
            'data'    => [
                'job_id'     => $job->uuid,
                'total_rows' => $job->total_rows,
                'status'     => 'queued',
                'status_url' => "/api/leads/import/{$job->uuid}/status",
            ],
            'message' => 'File received. Processing in background.',
        ], 202);
    }

    public function status(string $uuid)
    {
        $job = ImportJob::where('uuid', $uuid)
            ->where('company_id', app('current_company_id'))
            ->firstOrFail();

        return response()->json(['success' => true, 'data' => [
            'job_id'             => $job->uuid,
            'status'             => $job->status,
            'total_rows'         => $job->total_rows,
            'processed'          => $job->processed_rows,
            'created'            => $job->created_count,
            'duplicates_attached'=> $job->duplicate_count,
            'failed'             => $job->failed_count,
            'errors'             => $job->error_log ?? [],
        ]]);
    }
}
```

**`app/Jobs/ProcessImportRowJob.php`**

> **STABILITY FIX:** the original version did `->get()` on every pending
> staging row for the job — for 50k rows that's 50k models in memory inside
> a single worker, which also starves Horizon's other queues for the
> duration. This version processes in chunks of 200 and re-dispatches itself
> if rows remain, so each invocation is short and the worker pool stays
> available for other companies' jobs in between chunks.

```php
<?php
namespace App\Jobs;

use App\Models\ImportJob;
use App\Models\StagingLead;
use App\Services\LeadIntakeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ProcessImportRowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;
    const CHUNK_SIZE = 200;

    public function __construct(public int $importJobId) {}

    public function handle(LeadIntakeService $intakeService): void
    {
        $job = ImportJob::findOrFail($this->importJobId);

        if ($job->status === 'queued') {
            $job->update(['status' => 'processing']);
        }

        $rows = StagingLead::where('import_job_id', $this->importJobId)
            ->where('status', 'pending')
            ->limit(self::CHUNK_SIZE)
            ->get();

        if ($rows->isEmpty()) {
            $job->update(['status' => 'done']);
            return;
        }

        foreach ($rows as $row) {
            $row->update(['status' => 'processing']);
            try {
                $result = $intakeService->intake([
                    'name'  => $row->raw_name,
                    'phone' => $row->raw_phone,
                    'email' => $row->raw_email,
                    'source'=> $row->source,
                    'notes' => $row->raw_notes,
                ], $row->company_id, $job->uploaded_by_user_id);

                $row->update(['status' => 'done']);
                $job->increment('processed_rows');
                $result['is_duplicate'] ? $job->increment('duplicate_count') : $job->increment('created_count');
            } catch (\Exception $e) {
                $row->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
                $job->increment('processed_rows');
                $job->increment('failed_count');
                $errors   = $job->error_log ?? [];
                $errors[] = ['row' => $row->id, 'reason' => $e->getMessage()];
                $job->update(['error_log' => $errors]);
            }
        }

        // STABILITY: more pending rows remain — re-dispatch as a fresh job
        // instead of looping. Lets other jobs interleave between chunks.
        $remaining = StagingLead::where('import_job_id', $this->importJobId)
            ->where('status', 'pending')
            ->exists();

        if ($remaining) {
            self::dispatch($this->importJobId);
        } else {
            $job->update(['status' => 'done']);
        }
    }
}
```

---

### Day 6 — Routes + Seeders

**`routes/api.php`**

> **STABILITY FIX:** added `throttle` middleware to write endpoints.
> Without this, a retry loop or a mashed "submit" button on a slow
> connection can fire `POST /leads` repeatedly with no backpressure —
> at 560 concurrent users this is a self-inflicted load spike.

```php
<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\WebhookController;

// Public — auth itself is throttled to slow down credential stuffing
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Public — webhook has its own header-based auth + dedicated throttle (see WebhookController)
Route::post('/webhooks/leads', [WebhookController::class, 'intake'])->middleware('throttle:120,1');

// Protected + company-scoped
Route::middleware(['auth:sanctum', 'company.scope'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Leads — reads are not throttled beyond Sanctum defaults; writes are
    Route::get('/leads/check-duplicate', [LeadController::class, 'checkDuplicate']);
    Route::get('/leads', [LeadController::class, 'index']);
    Route::post('/leads', [LeadController::class, 'store'])->middleware('throttle:30,1');
    Route::get('/leads/{id}', [LeadController::class, 'show']);

    // CSV Import — heavier operation, stricter throttle
    Route::post('/leads/import', [ImportController::class, 'upload'])->middleware('throttle:5,1');
    Route::get('/leads/import/{uuid}/status', [ImportController::class, 'status']);

    // These routes are owned by Dev B and Dev C — do not implement, just leave stubs
    // Route::patch('/engagements/{id}/stage', ...)
    // Route::patch('/engagements/{id}/assign', ...)
    // Route::post('/engagements/{id}/activities', ...)
});
```

---

**`app/Http/Controllers/Api/WebhookController.php`** — **NEW, was missing from the original plan entirely**

> This endpoint is your most exposed surface: it's unauthenticated by
> Sanctum (external services like Meta Ads can't do an OAuth dance), so
> it relies entirely on a shared secret header. The original plan
> referenced this controller in routes but never specified it — meaning
> an agent would either invent something insecure or skip it.

```php
<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\LeadIntakeService;
use App\Jobs\ProcessImportRowJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    public function intake(Request $request)
    {
        // SECURITY: verify shared secret. Constant-time comparison via hash_equals
        // to avoid timing attacks on the key.
        $providedKey = $request->header('X-Webhook-Key', '');
        $expectedKey = config('app.webhook_secret_key'); // set from WEBHOOK_SECRET_KEY env

        if (!$providedKey || !hash_equals($expectedKey, $providedKey)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized', 'errors' => []], 401);
        }

        $request->validate([
            'name'         => 'required|string|max:255',
            'phone'        => 'required|string|max:20',
            'email'        => 'nullable|email|max:255',
            'source'       => 'nullable|string|max:50',
            'company_slug' => 'required|string|max:100',
            'meta'         => 'nullable|array',
        ]);

        // SECURITY: resolve company_slug -> company_id explicitly. Reject
        // unknown slugs with 404 rather than silently defaulting to any
        // company — prevents a misconfigured/malicious payload from landing
        // leads in the wrong tenant.
        $company = Company::where('slug', $request->company_slug)
            ->where('is_active', true)
            ->first();

        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Unknown company', 'errors' => []], 404);
        }

        $idempotencyKey = hash('sha256',
            $company->id . '|' .
            strtolower(trim($request->phone)) . '|' .
            ($request->source ?? 'webhook') . '|' .
            json_encode($request->meta ?? [])
        );

        // STABILITY: bulk-insert single row via DB facade — same pattern as
        // CSV import, avoids any synchronous LeadIntakeService call inside
        // the webhook request itself. The system user (id 0 / a dedicated
        // "system" user seeded by Dev C) is recorded as actor for queue processing.
        DB::table('staging_leads')->insertOrIgnore([[
            'company_id'      => $company->id,
            'import_job_id'   => null,
            'raw_phone'       => trim($request->phone),
            'raw_name'        => trim($request->name),
            'raw_email'       => $request->email ? trim($request->email) : null,
            'source'          => $request->source ?? 'webhook',
            'raw_notes'       => null,
            'meta'            => $request->meta ? json_encode($request->meta) : null,
            'status'          => 'pending',
            'idempotency_key' => $idempotencyKey,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]]);

        // Process this single staging row asynchronously
        \App\Jobs\ProcessWebhookStagingJob::dispatch($company->id, $idempotencyKey);

        return response()->json(['success' => true, 'data' => ['queued' => true], 'message' => 'Lead received'], 202);
    }
}
```

**`app/Jobs/ProcessWebhookStagingJob.php`** — **NEW**, small companion job:
```php
<?php
namespace App\Jobs;

use App\Models\StagingLead;
use App\Models\User;
use App\Services\LeadIntakeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessWebhookStagingJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;

    public function __construct(public int $companyId, public string $idempotencyKey) {}

    public function handle(LeadIntakeService $intakeService): void
    {
        $row = StagingLead::where('idempotency_key', $this->idempotencyKey)
            ->where('status', 'pending')
            ->first();

        if (!$row) {
            return; // already processed or never inserted (duplicate webhook fired)
        }

        $row->update(['status' => 'processing']);

        // Webhook leads have no human actor — use a seeded "system" user per
        // company (Dev C seeds this: role-less semantically, is_active=false,
        // used only for audit attribution on automated actions). Email format
        // is system+{company_slug}@internal.local — see SystemUserSeeder.
        $company = \App\Models\Company::find($this->companyId);
        $systemUserId = User::where('company_id', $this->companyId)
            ->where('email', 'system+' . $company->slug . '@internal.local')
            ->value('id');

        try {
            $intakeService->intake([
                'name'  => $row->raw_name,
                'phone' => $row->raw_phone,
                'email' => $row->raw_email,
                'source'=> $row->source,
                'notes' => $row->raw_notes,
            ], $this->companyId, $systemUserId);

            $row->update(['status' => 'done']);
        } catch (\Exception $e) {
            $row->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
        }
    }
}
```

Add to `.env`:
```env
WEBHOOK_SECRET_KEY=hilite_webhook_secret_change_in_prod
```

Add to `config/app.php`:
```php
'webhook_secret_key' => env('WEBHOOK_SECRET_KEY'),
```

**Seeders — `database/seeders/DatabaseSeeder.php`**
```php
public function run(): void
{
    $this->call([
        CompanySeeder::class,
        UserSeeder::class,
        PipelineStageSeeder::class,
        DispositionSeeder::class,
        LeadSeeder::class, // 20 sample leads for demo
    ]);
}
```

`CompanySeeder`: Create HiLITE Builders (slug: hilite-builders) and HiLITE Properties (slug: hilite-properties).

`UserSeeder`: Create 2–3 salespersons, 1 TL, 1 Manager per company. Bcrypt passwords. Assign company_id.

`PipelineStageSeeder`: Seed the 8 stages from API_CONTRACTS.md for each company with correct order + SLA days.

`LeadSeeder`: 20 leads with Indian/UAE phone numbers. Mix of assigned and unassigned. Mix of stages.

---

## Tests You Must Write (Day 6–7)

**`tests/Unit/PhoneNormalizationTest.php`**
- Indian 10-digit without prefix normalizes to +91XXXXXXXXXX
- Indian number with 0 prefix normalizes correctly
- UAE +971 number normalizes correctly
- Garbage string returns null
- Empty string returns null

**`tests/Feature/LeadDeduplicationTest.php`**
- Creating two leads with same phone returns is_duplicate = true on second
- Creating same phone from two different companies creates two engagements (not duplicate)
- Duplicate with active owner from same company returns 409 conflict
- Phone in different formats (+91 vs 91 vs 0) resolves to same lead
- **NEW:** simulate concurrent identical-phone inserts (two `intake()` calls
  for a brand-new phone in quick succession) — second call must NOT throw a
  500; it must return `is_duplicate: true` referencing the same lead created
  by the first call

**`tests/Feature/TenantIsolationTest.php`**
- Company A user cannot GET /leads and see Company B engagements
- Company A user cannot GET /leads/{id} for a Company B engagement

**`tests/Feature/LeadVisibilityTest.php`** — **NEW**
- Salesperson GET /leads only returns engagements where `assigned_user_id`
  is self or null — never another salesperson's assigned leads
- Salesperson GET /leads/{id} for a colleague's assigned engagement returns 403
- Salesperson GET /leads/{id} for an unassigned engagement returns 200
- Salesperson GET /leads/{id} for their own assigned engagement returns 200
- Team lead GET /leads/{id} for any engagement assigned within their team returns 200
- Team lead GET /leads/{id} for an engagement assigned to a different team's member returns 403
- Manager GET /leads/{id} for any engagement in the company returns 200

**`tests/Feature/DuplicateExposureTest.php`** — **NEW**
- POST /leads with a phone that's a duplicate, unassigned in this company —
  response `assigned_to` is null (not leaked)
- POST /leads with a phone duplicate already assigned to the requesting
  user — response `assigned_to` shows the requesting user's own info
- POST /leads with a phone duplicate assigned to a different active user —
  returns 409 with that user's name in the `message` (this is the one
  intentional disclosure — it's actionable, "go talk to them")
- GET /leads/check-duplicate as a plain salesperson for a phone assigned to
  a colleague — `assigned_to` and `stage` are null, but
  `engagement_exists_in_company` and `is_assigned` are still true
- GET /leads/check-duplicate as a team_lead for the same phone — `assigned_to`
  and `stage` ARE populated

**`tests/Feature/CsvImportLimitsTest.php`** — **NEW**
- CSV with rows missing `name` or `phone` are recorded in `error_log` and
  `failed_count`, never reach `LeadIntakeService`
- CSV exceeding `MAX_ROWS` (50,000) returns 422 immediately, job marked failed
- Upload returns 202 in well under request timeout regardless of file size
  up to the cap (mock/measure — don't actually upload 50k real rows in CI;
  test the chunking logic with a smaller `MAX_ROWS` override)
- `ProcessImportRowJob` re-dispatches itself when pending rows remain after
  one chunk, and does not load more than `CHUNK_SIZE` rows into memory at once

**`tests/Feature/WebhookIntakeTest.php`** — **NEW**
- POST /webhooks/leads without `X-Webhook-Key` header returns 401
- POST /webhooks/leads with wrong key returns 401
- POST /webhooks/leads with unknown `company_slug` returns 404
- POST /webhooks/leads with valid key + known slug returns 202 and creates
  a `staging_leads` row
- Duplicate webhook payload (same idempotency key) does not create a second
  staging row (insertOrIgnore)

**`tests/Feature/RateLimitTest.php`** — **NEW**
- POST /auth/login: 11th request within 1 minute returns 429
- POST /leads: 31st request within 1 minute from same user returns 429
- POST /leads/import: 6th request within 1 minute returns 429

Run with: `php artisan test`

---

## What You Hand Off to Dev B and Dev C

By end of Day 7 these must be ready and tested:
- All migrations run clean from fresh: `php artisan migrate:fresh --seed`
- `LeadIntakeService::intake()` is tested and working, including the
  concurrent-insert race-condition handling
- `POST /api/leads` returns correct 201/200/409, with assignee info
  redacted per the duplicate-exposure rules
- `GET /api/leads` returns paginated, company-scoped, **role-filtered** list
  with exact-match phone search and NULL-safe ordering
- `GET /api/leads/{id}` enforces ownership-based 403s for salesperson/team_lead
- `GET /api/leads/check-duplicate` redacts assignee info for non-privileged users
- `POST /api/auth/login` returns token, throttled at 10/min
- `POST /api/leads/import` streams CSV, bulk-inserts staging, caps at 50k rows
- `POST /api/webhooks/leads` validates shared secret + company_slug, queues via staging
- All seeders produce a working demo dataset, including a per-company
  `system+{slug}@internal.local` user for webhook audit attribution
- `AuditLogService` stub exists with `log()` method (Dev C fills in the internals)

---

## Do Not Do These (Dev B / Dev C Territory)
- Do not write `ActivityController`
- Do not write `AssignmentController` or `AssignmentService`
- Do not write SLA policy endpoints
- Do not write `AuditLogController` (you only call `AuditLogService::log()`)
- Do not touch React files

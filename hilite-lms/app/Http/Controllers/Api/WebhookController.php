<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    public function intake(Request $request)
    {
        $providedKey = $request->header('X-Webhook-Key', '');
        $expectedKey = config('app.webhook_secret_key');

        if (!$providedKey || !hash_equals($expectedKey, $providedKey)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized', 'errors' => (object)[]], 401);
        }

        $request->validate([
            'name'         => 'required|string|max:255',
            'phone'        => 'required|string|max:20',
            'email'        => 'nullable|email|max:255',
            'source'       => 'nullable|string|max:50',
            'company_slug' => 'required|string|max:100',
            'meta'         => 'nullable|array',
        ]);

        $company = Company::where('slug', $request->company_slug)
            ->where('is_active', true)
            ->first();

        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Unknown company', 'errors' => (object)[]], 404);
        }

        $idempotencyKey = hash('sha256',
            $company->id . '|' .
            strtolower(trim($request->phone)) . '|' .
            ($request->source ?? 'webhook') . '|' .
            json_encode($request->meta ?? [])
        );

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

        \App\Jobs\ProcessWebhookStagingJob::dispatch($company->id, $idempotencyKey);

        return response()->json(['success' => true, 'data' => ['queued' => true], 'message' => 'Lead received'], 202);
    }
}

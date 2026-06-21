<?php
namespace App\Jobs;

use App\Models\StagingLead;
use App\Models\User;
use App\Models\Company;
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
            return;
        }

        $row->update(['status' => 'processing']);

        $company = Company::find($this->companyId);
        $systemUserId = User::where('company_id', $this->companyId)
            ->where('email', 'system+' . $company->slug . '@internal.local')
            ->value('id');

        $meta = json_decode($row->meta, true) ?? [];

        try {
            $intakeService->intake([
                'name'        => $row->raw_name,
                'phone'       => $row->raw_phone,
                'email'       => $row->raw_email,
                'source'      => $row->source,
                'notes'       => $row->raw_notes,
                'occurred_at' => $meta['occurred_at'] ?? now()->toIso8601String(),
            ], $this->companyId, $systemUserId);

            $row->update(['status' => 'done']);
        } catch (\Exception $e) {
            $row->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
        }
    }
}

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

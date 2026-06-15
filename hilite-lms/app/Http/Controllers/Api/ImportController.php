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
    const MAX_ROWS   = 50000;

    public function upload(Request $request)
    {
        $request->validate([
            'file'   => 'required|file|mimes:csv,txt|max:10240',
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
            return response()->json(['success' => false, 'message' => 'CSV file is empty', 'errors' => (object)[]], 422);
        }
        $headers = array_map(fn($h) => strtolower(trim($h)), $headerRow);

        $job = ImportJob::create([
            'uuid'                => Str::uuid(),
            'company_id'          => $companyId,
            'uploaded_by_user_id' => $userId,
            'original_filename'   => $file->getClientOriginalName(),
            'status'              => 'queued',
            'total_rows'          => 0,
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

            if ($totalRows > self::MAX_ROWS) {
                $job->update([
                    'status'     => 'failed',
                    'error_log'  => [['row' => self::MAX_ROWS + 1, 'reason' => 'File exceeds max ' . self::MAX_ROWS . ' rows. Please split into smaller files.']],
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'File exceeds the maximum of ' . self::MAX_ROWS . ' rows. Please split into smaller files.',
                    'errors'  => (object)[]
                ], 422);
            }

            $data = array_combine($headers, array_pad($row, count($headers), null));

            $rawPhone = trim((string) ($data['phone'] ?? ''));
            $rawName  = trim((string) ($data['name'] ?? ''));

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

            if (count($batch) >= self::BATCH_SIZE) {
                DB::table('staging_leads')->insertOrIgnore($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('staging_leads')->insertOrIgnore($batch);
        }

        $job->update(['total_rows' => $totalRows]);

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

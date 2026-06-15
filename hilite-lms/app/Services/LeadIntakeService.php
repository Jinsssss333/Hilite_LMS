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
            if ((int) $e->getCode() === 23000) {
                $lead = Lead::where('phone_e164', $phone)->first();
                if (!$lead) {
                    throw $e;
                }
            } else {
                throw $e;
            }
        }

        // Step 2: Check if this company already has an engagement for this lead
        $existing = LeadEngagement::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('lead_id', $lead->id)
            ->first();

        if ($existing) {
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

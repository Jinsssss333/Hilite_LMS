<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PipelineStage;

class PipelineStageController extends Controller
{
    /**
     * GET /api/pipeline-stages
     * Returns all pipeline stages for the current company, ordered by 'order'.
     */
    public function index()
    {
        $companyId = app('current_company_id');

        $stages = PipelineStage::forCompany($companyId)
            ->get()
            ->map(fn($s) => [
                'id'        => $s->id,
                'name'      => $s->name,
                'order'     => $s->order,
                'color'     => $s->color,
                'sla_days'  => $s->sla_days,
                'is_closed' => $s->is_closed,
            ]);

        return response()->json(['success' => true, 'data' => $stages]);
    }
}

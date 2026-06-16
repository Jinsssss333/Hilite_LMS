<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Disposition;
use Illuminate\Http\Request;

class DispositionController extends Controller
{
    /**
     * GET /api/dispositions
     * Returns dispositions for the current company.
     * Optional filter: ?stage_id=3 returns stage-specific + global (null stage_id) dispositions.
     */
    public function index(Request $request)
    {
        $companyId = app('current_company_id');

        $dispositions = Disposition::where('company_id', $companyId)
            ->when($request->stage_id, fn($q) =>
                $q->where(fn($inner) =>
                    $inner->where('stage_id', $request->stage_id)
                          ->orWhereNull('stage_id') // global dispositions always included
                )
            )
            ->orderBy('label')
            ->get()
            ->map(fn($d) => [
                'id'       => $d->id,
                'label'    => $d->label,
                'stage_id' => $d->stage_id,
            ]);

        return response()->json(['success' => true, 'data' => $dispositions]);
    }
}

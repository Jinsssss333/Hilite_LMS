<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadEngagement;
use App\Services\AssignmentService;
use App\Http\Helpers\AuthHelper;

class AssignmentHubController extends Controller
{
    public function __construct(protected AssignmentService $assignmentService) {}

    public function index(Request $request)
    {
        $user = AuthHelper::user();
        if ($user && $user->role === 'salesperson') {
            return redirect()->route('leads.index');
        }
        
        $query = LeadEngagement::whereNull('assigned_user_id')->with(['lead', 'stage']);
        
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('lead', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_e164', 'like', "%{$search}%");
            });
        }
        
        $unassigned = $query->paginate(20);
        $assignableUsers = $this->assignmentService->getAssignableUsers($user);

        return view('dashboard.assignment', compact('unassigned', 'assignableUsers'));
    }

    public function assign(Request $request)
    {
        $user = AuthHelper::user();
        
        $request->validate([
            'engagement_ids' => 'required|array|min:1',
            'engagement_ids.*' => 'exists:lead_engagements,id',
            'assign_to_user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:255'
        ]);

        $successCount = 0;
        foreach ($request->engagement_ids as $engagementId) {
            $engagement = LeadEngagement::findOrFail($engagementId);
            try {
                $this->assignmentService->assign(
                    $engagement,
                    $request->assign_to_user_id,
                    $user->id,
                    $request->reason
                );
                $successCount++;
            } catch (\Exception $e) {
                // Ignore failures to allow partial success, or log them
                // You could flash an error array if needed
            }
        }

        return redirect()->back()->with('success', "Successfully assigned {$successCount} lead(s).");
    }
}

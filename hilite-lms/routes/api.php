<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Public — auth itself is throttled to slow down credential stuffing
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Public — webhook has its own header-based auth + dedicated throttle
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

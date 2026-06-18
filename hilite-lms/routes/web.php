<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;

// ─── Public routes (no auth required) ───────────────────────────────────────

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $user = \App\Models\User::where('email', $request->email)->first();
    if ($user && Hash::check($request->password, $user->password)) {
        session([
            'user_id'   => $user->id,
            'user_role' => $user->role,
            'user_name' => $user->name,
        ]);
        return redirect()->route('leads.index');
    }
    return back()
        ->withErrors(['email' => 'Invalid credentials. Check email/password.'])
        ->withInput();
})->name('login.post');

// ─── Protected routes (require session auth) ─────────────────────────────────

Route::middleware('auth.lms')->group(function () {

    Route::post('/logout', function () {
        session()->flush();
        return redirect()->route('login');
    })->name('logout');

    // Dashboards
    Route::get('/dashboard', function () {
        return view('dashboard.salesperson');
    })->name('dashboard.salesperson');

    Route::get('/dashboard/manager', function () {
        return view('dashboard.manager');
    })->name('dashboard.manager');

    Route::get('/dashboard/assignment', function () {
        return view('dashboard.assignment');
    })->name('dashboard.assignment');

    // Leads
    Route::get('/leads', [\App\Http\Controllers\LeadsController::class, 'index'])->name('leads.index');
    Route::patch('/leads/{id}/stage', [\App\Http\Controllers\LeadsController::class, 'updateStage'])->name('leads.update-stage');

    Route::get('/leads/followups', function () {
        return view('leads.followups');
    })->name('leads.followups');

    Route::get('/leads/import', function () {
        return view('leads.import');
    })->name('leads.import');

    Route::post('/leads/import', [\App\Http\Controllers\LeadsController::class, 'processImport'])->name('leads.import.post');
    Route::post('/leads/manual', [\App\Http\Controllers\LeadsController::class, 'processManual'])->name('leads.manual.post');

    Route::get('/leads/calendar', function () {
        return view('leads.calendar');
    })->name('leads.calendar');

    // Archive removed — redirect to leads to avoid errors
    Route::get('/leads/archive', function () {
        return redirect()->route('leads.index');
    })->name('leads.archive');

    // Reports (heatmap merged inside)
    Route::get('/reports', [\App\Http\Controllers\ReportsController::class, 'index'])->name('reports.index');

    // Heatmap merged into reports — redirect for safety
    Route::get('/reports/heatmap', function () {
        return redirect()->route('reports.index');
    })->name('reports.heatmap');

    // Admin & Profile
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AdminController::class, 'index'])->name('index');
        Route::get('/users', [\App\Http\Controllers\AdminController::class, 'users'])->name('users');
        Route::get('/pipeline', [\App\Http\Controllers\AdminController::class, 'pipeline'])->name('pipeline');
        Route::get('/sla', [\App\Http\Controllers\AdminController::class, 'sla'])->name('sla');
        Route::get('/audit', [\App\Http\Controllers\AdminController::class, 'audit'])->name('audit');
    });

    Route::get('/profile', function () {
        return view('profile.settings');
    })->name('profile.settings');
    
    Route::post('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
});

<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ─── Test UI Routes (Blade-based, talks to API via JS) ─────────
Route::get('/test/login', fn() => view('test.login'));
Route::get('/test/dashboard', fn() => view('test.dashboard'));
Route::get('/test/leads', fn() => view('test.leads.index'));
Route::get('/test/leads/create', fn() => view('test.leads.create'));
Route::get('/test/leads/{id}', fn($id) => view('test.leads.show', ['id' => $id]));
Route::get('/test/activities', fn() => view('test.activities'));
Route::get('/test/import', fn() => view('test.import'));
Route::get('/test/pipeline', fn() => view('test.pipeline'));
Route::get('/test/users', fn() => view('test.users'));
Route::get('/test/audit', fn() => view('test.audit'));
Route::get('/test/sla', fn() => view('test.sla'));

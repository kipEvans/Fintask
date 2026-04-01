<?php
// ============================================================
// routes/web.php
// Web (browser) routes for FinTask.
// ============================================================

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/
Route::get('/',        [WebController::class, 'home'])->name('home');
Route::get('/login',   [WebController::class, 'showLogin'])->name('login');
Route::post('/login',  [WebController::class, 'login'])->name('login.post');

Route::get('/register',  [WebController::class, 'showRegister'])->name('register');
Route::post('/register', [WebController::class, 'register'])->name('register.post');

/*
|--------------------------------------------------------------------------
| Protected routes (session JWT required)
|--------------------------------------------------------------------------
*/
Route::middleware(['web', \App\Http\Middleware\WebAuth::class])->group(function () {
    Route::get('/dashboard', [WebController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout',   [WebController::class, 'logout'])->name('logout');
});


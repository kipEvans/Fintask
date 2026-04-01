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

Route::get('/_health', fn () => response('ok', 200));
Route::get('/_debug', function () {
    return response()->json([
        'APP_ENV' => env('APP_ENV', 'undefined'),
        'APP_DEBUG' => env('APP_DEBUG', 'undefined'),
        'APP_KEY' => env('APP_KEY', 'undefined'),
        'DB_CONNECTION' => env('DB_CONNECTION', 'undefined'),
        'DB_HOST' => env('DB_HOST', 'undefined'),
        'DB_DATABASE' => env('DB_DATABASE', 'undefined'),
        'php' => phpversion(),
    ]);
});

/*
|--------------------------------------------------------------------------
| Protected routes (session JWT required)
|--------------------------------------------------------------------------
*/
Route::middleware(['web', \App\Http\Middleware\WebAuth::class])->group(function () {
    Route::get('/dashboard', [WebController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout',   [WebController::class, 'logout'])->name('logout');
});


<?php
// ============================================================
// app/Http/Middleware/WebAuth.php
// Protects web (non-API) routes by checking the session token.
// ============================================================

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebAuth
{
    /**
     * If there is no JWT in the session, redirect to login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('jwt_token')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
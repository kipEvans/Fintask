<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class WebController extends Controller
{
    public function home()
    {
        if (session('jwt_token')) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('login');
    }

    public function showLogin()
    {
        if (session('jwt_token')) {
            return redirect()->route('dashboard');
        }

        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Invalid credentials. Please try again.']);
        }

        try {
            JWTAuth::setToken($token);
            $authedUser = JWTAuth::user();
        } catch (\Exception $e) {
            $authedUser = null;
        }

        $request->session()->put('jwt_token', $token);
        $request->session()->put('user_name', $authedUser?->name ?: $request->email);

        return redirect()->route('dashboard');
    }

    public function showRegister()
    {
        return view('register');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:100',
            'email'           => 'required|email|unique:users,email',
            'password'        => 'required|min:8|confirmed',
            'monthly_budget'  => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }

        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'password'       => Hash::make($request->password),
            'monthly_budget' => $request->monthly_budget ?? 50000,
            'currency'       => 'KES',
        ]);

        // do not auto-login after registration; require explicit login
        return redirect()->route('login')->with('success', 'Account created. Please log in.');
    }

    public function dashboard()
    {
        return view('dashboard');
    }

    public function logout(Request $request)
    {
        try {
            $token = $request->session()->get('jwt_token');
            if ($token) {
                JWTAuth::setToken($token)->invalidate();
            }
        } catch (\Exception $e) {
            // token may already be expired
        }

        $request->session()->forget(['jwt_token', 'user_name']);

        return redirect()->route('login');
    }
}

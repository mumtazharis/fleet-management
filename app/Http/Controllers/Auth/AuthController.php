<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Show login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Activity Log
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'LOGIN',
                'entity_type' => 'User',
                'entity_id' => Auth::id(),
                'description' => 'User ' . Auth::user()->name . ' berhasil login ke sistem.',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang kembali, ' . Auth::user()->name . '!');
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'LOGOUT',
                'entity_type' => 'User',
                'entity_id' => Auth::id(),
                'description' => 'User ' . Auth::user()->name . ' telah logout dari sistem.',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah keluar dari sistem.');
    }
}

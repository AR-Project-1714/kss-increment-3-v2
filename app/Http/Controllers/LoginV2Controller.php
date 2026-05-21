<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginV2Controller extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole();
        }

        return view('auth.index');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $login = trim($credentials['username']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $remember = $request->boolean('remember');

        if (Auth::attempt([$field => $login, 'password' => $credentials['password']], $remember)) {
            RateLimiter::clear($this->throttleKey($request));
            $request->session()->regenerate();

            $user = $request->user();
            if (($user->status ?? 'aktif') !== 'aktif') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'username' => 'Akun Anda dinonaktifkan. Silakan hubungi admin.',
                ]);
            }

            return $this->redirectBasedOnRole();
        }

        RateLimiter::hit($this->throttleKey($request), 60);

        throw ValidationException::withMessages([
            'username' => 'Username/email atau password salah.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function redirectBasedOnRole()
    {
        $user = Auth::user();
        $roleName = strtolower((string) ($user->role->name ?? ''));

        if ($roleName === Role::MANAGER && Route::has('manajer.index')) {
            return redirect()->route('manajer.index');
        }

        if ($roleName === Role::ADMIN && Route::has('admin.index')) {
            return redirect()->route('admin.index');
        }

        return redirect()->route('report-ops.index');
    }

    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'username' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('username')).'|'.$request->ip());
    }
}

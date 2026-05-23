<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginV2Controller extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOGIN_DECAY_SECONDS = 60;

    private const MAX_IP_ATTEMPTS = 20;

    private const IP_DECAY_SECONDS = 300;

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
                $this->recordLoginSecurityEvent(
                    $request,
                    'Login ditolak karena akun nonaktif: '.$login,
                    $user,
                    [
                        'event' => 'inactive_account_login',
                        'attempted_login' => $login,
                        'field' => $field,
                    ]
                );

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'username' => 'Akun Anda dinonaktifkan. Silakan hubungi admin.',
                ]);
            }

            return $this->redirectBasedOnRole();
        }

        RateLimiter::hit($this->throttleKey($request), self::LOGIN_DECAY_SECONDS);
        RateLimiter::hit($this->ipThrottleKey($request), self::IP_DECAY_SECONDS);

        $attempts = RateLimiter::attempts($this->throttleKey($request));
        $attemptedUser = $this->attemptedUser($field, $login);

        $this->recordLoginSecurityEvent(
            $request,
            'Login gagal untuk username/email "'.$login.'" (percobaan '.$attempts.'/'.self::MAX_LOGIN_ATTEMPTS.').',
            $attemptedUser,
            [
                'event' => 'failed_login',
                'attempted_login' => $login,
                'field' => $field,
                'attempts' => $attempts,
                'max_attempts' => self::MAX_LOGIN_ATTEMPTS,
                'ip_attempts' => RateLimiter::attempts($this->ipThrottleKey($request)),
                'ip_max_attempts' => self::MAX_IP_ATTEMPTS,
                'user_agent' => (string) $request->userAgent(),
            ]
        );

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
        return redirect()->route(Role::homeRoute(Auth::user()?->role->name ?? null));
    }

    private function ensureIsNotRateLimited(Request $request): void
    {
        $limits = [
            [
                'key' => $this->throttleKey($request),
                'max' => self::MAX_LOGIN_ATTEMPTS,
                'event' => 'identity_rate_limited',
                'description' => 'Brute force login diblokir untuk username/email "'.trim((string) $request->input('username')).'".',
            ],
            [
                'key' => $this->ipThrottleKey($request),
                'max' => self::MAX_IP_ATTEMPTS,
                'event' => 'ip_rate_limited',
                'description' => 'Brute force login diblokir dari IP '.$request->ip().'.',
            ],
        ];

        foreach ($limits as $limit) {
            if (! RateLimiter::tooManyAttempts($limit['key'], $limit['max'])) {
                continue;
            }

            $seconds = RateLimiter::availableIn($limit['key']);
            $this->recordLoginLockoutOnce($request, $limit['key'], $limit['description'], $limit['event'], $seconds);

            throw ValidationException::withMessages([
                'username' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }
    }

    private function throttleKey(Request $request): string
    {
        return 'login:identity:'.sha1(Str::transliterate(Str::lower(trim((string) $request->input('username')))).'|'.$request->ip());
    }

    private function ipThrottleKey(Request $request): string
    {
        return 'login:ip:'.sha1((string) $request->ip());
    }

    private function attemptedUser(string $field, string $login): ?User
    {
        return User::query()->where($field, $login)->first();
    }

    private function recordLoginLockoutOnce(Request $request, string $limitKey, string $description, string $event, int $seconds): void
    {
        $logKey = 'login:security-log:'.sha1($limitKey);

        if (RateLimiter::tooManyAttempts($logKey, 1)) {
            return;
        }

        RateLimiter::hit($logKey, max($seconds, 1));

        $login = trim((string) $request->input('username'));
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $this->recordLoginSecurityEvent(
            $request,
            $description,
            $this->attemptedUser($field, $login),
            [
                'event' => $event,
                'attempted_login' => $login,
                'field' => $field,
                'retry_after_seconds' => $seconds,
                'user_agent' => (string) $request->userAgent(),
            ]
        );
    }

    private function recordLoginSecurityEvent(Request $request, string $description, ?User $user = null, array $properties = []): void
    {
        AdminActivityLog::create([
            'user_id' => $user?->id,
            'type' => 'security',
            'description' => $description,
            'ip_address' => $request->ip(),
            'properties' => $properties ?: null,
        ]);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict a route group by role.
 *
 * Allow-list usage:  ->middleware('role:admin')  or  ->middleware('role:operasional,pemeliharaan,safety')
 * Deny-list usage:   ->middleware('role:except,admin,manajer')  (everyone EXCEPT the listed roles)
 *
 * The deny-list form is used for the report-ops (petugas operasional) area so it
 * stays the default landing area for every non-management account, while still
 * locking out admin and manajer.
 *
 * Users who are not permitted are sent back to their OWN dashboard (clean
 * fallback redirect) for page requests, or receive a 403 for JSON/API requests.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            $message = 'Sesi Anda telah habis, silakan login kembali.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 401);
            }

            return redirect()->guest(route('login'))->with('error', $message);
        }

        $roleName = Role::normalize($user->role->name ?? null);

        $deny = ($roles[0] ?? null) === 'except';
        if ($deny) {
            $roles = array_slice($roles, 1);
        }

        $list = array_map([Role::class, 'normalize'], $roles);
        $permitted = $deny
            ? ! in_array($roleName, $list, true)
            : in_array($roleName, $list, true);

        if ($permitted) {
            return $next($request);
        }

        $message = 'Anda tidak memiliki akses ke halaman tersebut.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        // Avoid a redirect loop: only bounce roles that have a known dashboard
        // that isn't the page we just denied. Otherwise, fail closed with 403.
        $home = Role::homeRoute($roleName);

        if (! Role::hasKnownHome($roleName) || $request->routeIs($home)) {
            abort(403, $message);
        }

        return redirect()->route($home)->with('error', $message);
    }
}

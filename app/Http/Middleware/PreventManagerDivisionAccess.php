<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventManagerDivisionAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $roleName = strtolower((string) ($request->user()?->role->name ?? ''));

        if ($roleName !== Role::MANAGER) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Akun manajer hanya dapat mengakses halaman manajer.',
            ], 403);
        }

        return redirect()
            ->route('manajer.index')
            ->with('error', 'Akun manajer hanya dapat mengakses halaman manajer.');
    }
}

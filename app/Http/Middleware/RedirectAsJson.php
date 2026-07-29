<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turn a RedirectResponse into {"redirect": "<url>"} for clients that asked for
 * JSON, leaving normal browser requests untouched.
 *
 * Dipakai oleh route approve/TTD: overlay progres PDF mengirim form lewat fetch
 * supaya tahu KAPAN proses di server selesai (bar baru boleh lompat ke 100%).
 * Kalau server tetap membalas redirect, fetch akan ikut memuat halaman tujuan
 * dan flash message-nya habis di request itu — halaman tidak lagi menampilkannya
 * saat browser benar-benar pindah. Flash yang dipasang lewat `with()` sudah
 * masuk session sebelum middleware ini berjalan, jadi tetap tampil setelah
 * halaman tujuan dibuka oleh JS.
 */
class RedirectAsJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof RedirectResponse && $request->expectsJson()) {
            return new JsonResponse(['redirect' => $response->getTargetUrl()]);
        }

        return $response;
    }
}

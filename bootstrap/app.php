<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Percayai header reverse proxy / Cloudflare (X-Forwarded-For, X-Forwarded-Proto, dll.)
        // agar HTTPS dikenali dengan benar saat deployment di production.
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('wishlist*')) {
                session()->flash('error', 'Silakan masuk terlebih dahulu untuk mengakses dan menyimpan barang ke Wishlist.');
            } elseif ($request->is('chat*') || $request->is('barang/*/chat*')) {
                session()->flash('error', 'Silakan masuk terlebih dahulu untuk menghubungi penjual atau membuka Room Chat.');
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

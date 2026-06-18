<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/cp.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
        $middleware->appendToGroup('web', \App\Http\Middleware\TrackUserOnlineStatus::class);
        $middleware->redirectGuestsTo(fn (Request $request) => $request->expectsJson() ? null : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

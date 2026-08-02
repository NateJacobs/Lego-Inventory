<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Where the "guest" middleware sends someone who is already signed in.
        // The old RedirectIfAuthenticated sent them to /home, a route this
        // application has never had, so visiting /login while logged in 404'd.
        $middleware->redirectUsersTo('/collection');

        // Matches the throttle the api middleware group carried before.
        $middleware->throttleApi('60,1');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

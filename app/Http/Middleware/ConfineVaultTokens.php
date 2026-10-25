<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * A token made for vault.ngtj.com may read /api/vault and nothing else.
 *
 * The other API routes only ask for *a* valid token, so without this a leaked vault
 * token could read the catalog routes. Full-access tokens (`*`) are
 * not affected.
 */
class ConfineVaultTokens
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user('sanctum')?->currentAccessToken();

        if ($token instanceof PersonalAccessToken
            && $token->can('vault:read')
            && ! $token->can('*')
            && ! $request->is('api/vault/*')) {
            abort(403, 'This token can only read /api/vault.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return $next($request);
        }

        // Attach org context for downstream use
        $request->attributes->set('organization_id', $request->user()->organization_id);

        return $next($request);
    }
}

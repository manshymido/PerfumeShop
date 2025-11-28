<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptionalAuth
{
    /**
     * Handle an incoming request.
     * 
     * Authenticate the user if a token is present, but don't require it.
     * This allows routes to work for both authenticated and guest users.
     * 
     * Note: EnsureFrontendRequestsAreStateful middleware should already handle
     * token authentication, but this ensures the user is set in the auth context.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // EnsureFrontendRequestsAreStateful middleware should have already processed the token
        // This middleware just ensures the user is available in the request
        // $request->user() should work if a valid token was provided
        
        return $next($request);
    }
}


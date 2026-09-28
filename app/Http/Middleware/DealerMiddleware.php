<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dealer panel gate: must be logged in on the `dealer` guard with an approved account.
 * A dealer suspended while logged in is signed out on their next request.
 */
class DealerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('dealer');

        if (! $guard->check()) {
            return redirect()->guest(route('business.login'));
        }

        if (! $guard->user()->isApproved()) {
            $guard->logout();

            return redirect()->route('business.login')->with('error', 'Your business account is not active. Please contact the admin.');
        }

        return $next($request);
    }
}

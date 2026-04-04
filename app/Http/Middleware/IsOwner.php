<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->user_type === 'owner') {
            return $next($request);
        }

        if ($this->expectsApiResponse($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        return redirect()->route('home')->with('error', 'Unauthorized access.');
    }

    private function expectsApiResponse(Request $request): bool
    {
        return $request->expectsJson() || $request->is('api/*');
    }
}

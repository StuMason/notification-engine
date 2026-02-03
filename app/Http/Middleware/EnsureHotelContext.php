<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHotelContext
{
    /**
     * Ensure the authenticated user has a hotel_id for tenant scoping.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hotel_id) {
            abort(403, 'No hotel context available.');
        }

        return $next($request);
    }
}

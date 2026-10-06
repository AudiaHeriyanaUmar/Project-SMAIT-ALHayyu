<?php

namespace App\Http\Middleware;

use App\Services\AccountActivityTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackAccountActivity
{
    public function __construct(private readonly AccountActivityTracker $activityTracker) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $this->activityTracker->record($user, $request->session()->getId());
        }

        return $next($request);
    }
}

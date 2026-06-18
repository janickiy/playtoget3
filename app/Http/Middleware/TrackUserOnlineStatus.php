<?php

namespace App\Http\Middleware;

use App\Service\UserOnlineStatusService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserOnlineStatus
{
    public function __construct(private readonly UserOnlineStatusService $onlineStatus)
    {
    }

    /**
     * Stores the current user's last activity time for online indicators.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user) {
            $this->onlineStatus->touch($user);
        }

        return $next($request);
    }
}

<?php

namespace App\Service;

use App\Models\Log;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthLogService
{
    /**
     * Stores a successful user authorization entry.
     */
    public function record(User $user, Request $request): Log
    {
        /** @var Log $log */
        $log = Log::query()->create([
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            'last_sign_in_at' => now(),
        ]);

        return $log;
    }
}

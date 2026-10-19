<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditLog
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && $request->is('api/*')) {
            $user = $request->user();

            Log::channel('stack')->info('AUDIT', [
                'user_id' => $user?->id,
                'role'     => $user?->role,
                'ip'       => $request->ip(),
                'method'   => $request->method(),
                'path'     => $request->path(),
                'status'   => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
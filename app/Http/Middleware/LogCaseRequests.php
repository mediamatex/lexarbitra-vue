<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogCaseRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('LogCaseRequests::handle - Incoming case request', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'route_name' => $request->route()?->getName(),
            'route_parameters' => $request->route()?->parameters() ?? [],
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $response = $next($request);

        Log::info('LogCaseRequests::handle - Case request completed', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'route_name' => $request->route()?->getName(),
            'status_code' => $response->getStatusCode(),
            'user_id' => auth()->id(),
        ]);

        return $response;
    }
}

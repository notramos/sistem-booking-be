<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DebugRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $method = $request->method();
        $path = $request->path();
        $token = $request->bearerToken() ?: 'NONE';
        $auth = $request->header('Authorization', 'NONE');

        $stderr = fopen('php://stderr', 'w');
        fwrite($stderr, PHP_EOL);
        fwrite($stderr, "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL);
        fwrite($stderr, "  >>> {$method} /{$path}" . PHP_EOL);
        fwrite($stderr, "  Authorization: {$auth}" . PHP_EOL);
        fwrite($stderr, "  Bearer token: " . ($token !== 'NONE' ? substr($token, 0, 30) . '...' : 'NONE') . PHP_EOL);
        fwrite($stderr, "  Accept: " . $request->header('Accept', 'NONE') . PHP_EOL);
        fwrite($stderr, "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL);

        $response = $next($request);

        fwrite($stderr, "  <<< {$response->getStatusCode()}" . PHP_EOL);
        fclose($stderr);

        return $response;
    }
}

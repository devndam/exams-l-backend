<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Support\AuthContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authorize
{
    public function __construct(private readonly AuthContext $auth) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $this->auth->role || ! in_array($this->auth->role, $roles, true)) {
            throw new ApiException(403, 'Access denied');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\Candidate;
use App\Support\AuthContext;
use App\Support\Constants;
use App\Support\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Authenticate
{
    public function __construct(
        private readonly JwtService $jwt,
        private readonly AuthContext $auth,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            throw new ApiException(401, 'Authentication required');
        }

        $token = substr($header, 7);

        try {
            $decoded = $this->jwt->verify($token);
        } catch (Throwable) {
            throw new ApiException(401, 'Invalid or expired token');
        }

        if (($decoded['role'] ?? null) === Constants::ROLE_CANDIDATE) {
            $candidate = Candidate::query()->select('session_token')->find($decoded['id']);

            if (! $candidate || $candidate->session_token !== ($decoded['sessionToken'] ?? null)) {
                throw new ApiException(401, 'Your session was ended because your account logged in on another device.');
            }
        }

        $this->auth->id = (int) $decoded['id'];
        $this->auth->role = $decoded['role'] ?? null;
        $this->auth->sessionToken = $decoded['sessionToken'] ?? null;

        // Laravel's Pusher/Reverb broadcaster requires $request->user() to be truthy
        // for private/presence channels (see Broadcasters/PusherBroadcaster::auth())
        // regardless of what a Broadcast::channel() callback decides — we don't use
        // Laravel's native auth guards, so without this every /broadcasting/auth
        // request would 403 before our channel authorization logic (routes/channels.php,
        // driven by AuthContext) ever runs.
        $request->setUserResolver(fn () => (object) [
            'id' => $this->auth->id,
            'role' => $this->auth->role,
        ]);

        return $next($request);
    }
}

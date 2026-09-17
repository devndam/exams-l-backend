<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;

class SessionController extends Controller
{
    public function __construct(private readonly SessionService $sessionService) {}

    public function active(): JsonResponse
    {
        return $this->success($this->sessionService->getActiveSessions());
    }
}

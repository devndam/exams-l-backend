<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Http\Requests\Auth\CandidateLoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function adminLogin(AdminLoginRequest $request): JsonResponse
    {
        $result = $this->authService->loginAdmin($request->string('email')->toString(), $request->string('password')->toString());

        return $this->success($result, 'Login successful');
    }

    public function candidateLogin(CandidateLoginRequest $request): JsonResponse
    {
        $result = $this->authService->loginCandidate($request->string('candidateId')->toString());

        return $this->success($result, 'Login successful');
    }
}

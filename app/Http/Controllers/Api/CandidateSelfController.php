<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\UpdateCertificateRequest;
use App\Services\CandidateService;
use App\Support\AuthContext;
use Illuminate\Http\JsonResponse;

class CandidateSelfController extends Controller
{
    public function __construct(
        private readonly CandidateService $candidateService,
        private readonly AuthContext $auth,
    ) {}

    public function myExams(): JsonResponse
    {
        return $this->success($this->candidateService->getCandidateExams($this->auth->id));
    }

    public function updateCertificate(UpdateCertificateRequest $request): JsonResponse
    {
        $candidate = $this->candidateService->updateCertificateDetails($this->auth->id, $request->validated());

        return $this->success($candidate, 'Certificate details updated successfully');
    }
}

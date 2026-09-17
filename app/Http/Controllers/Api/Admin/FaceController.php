<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Face\ReviewEnrollmentRequest;
use App\Services\Face\FaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaceController extends Controller
{
    public function __construct(private readonly FaceService $faceService) {}

    public function flagged(): JsonResponse
    {
        return $this->success($this->faceService->getFlaggedSessions());
    }

    public function monitoringEvents(int $sessionId): JsonResponse
    {
        return $this->success($this->faceService->getSessionMonitoringEvents($sessionId));
    }

    public function captureImage(int $candidateId): JsonResponse
    {
        return $this->success($this->faceService->getCaptureImage($candidateId));
    }

    public function pendingEnrollments(): JsonResponse
    {
        return $this->success($this->faceService->getPendingEnrollments());
    }

    public function reviewEnrollment(int $candidateId, ReviewEnrollmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->faceService->reviewEnrollment($candidateId, $data['status'], $data['reviewNote'] ?? null);

        return $this->success($result, "Face enrollment {$data['status']}");
    }

    public function resetEnrollment(int $candidateId): JsonResponse
    {
        return $this->success($this->faceService->resetEnrollment($candidateId), 'Face enrollment reset. Candidate must re-enroll.');
    }

    public function livenessSessions(Request $request): JsonResponse
    {
        $candidateId = $request->query('candidateId') ? (int) $request->query('candidateId') : null;

        return $this->success($this->faceService->getLivenessSessions($candidateId));
    }
}

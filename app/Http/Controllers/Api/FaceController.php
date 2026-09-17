<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Face\EnrollFaceRequest;
use App\Http\Requests\Face\LivenessCompleteRequest;
use App\Http\Requests\Face\LivenessSessionRequest;
use App\Http\Requests\Face\MonitorAudioRequest;
use App\Http\Requests\Face\MonitorFrameRequest;
use App\Http\Requests\Face\VerifyFaceRequest;
use App\Services\Face\FaceService;
use App\Support\AuthContext;
use Illuminate\Http\JsonResponse;

class FaceController extends Controller
{
    public function __construct(
        private readonly FaceService $faceService,
        private readonly AuthContext $auth,
    ) {}

    public function enroll(EnrollFaceRequest $request): JsonResponse
    {
        $result = $this->faceService->enrollFace($this->auth->id, $request->string('image')->toString());

        return $this->success($result, 'Face enrolled successfully', 201);
    }

    public function verify(VerifyFaceRequest $request): JsonResponse
    {
        $result = $this->faceService->verifyFace($this->auth->id, $request->string('image')->toString(), null, $request->ip());

        return $this->success($result, $result['verified'] ? 'Face verified successfully' : 'Face does not match enrolled face');
    }

    public function status(): JsonResponse
    {
        return $this->success($this->faceService->getEnrollmentStatus($this->auth->id));
    }

    public function monitor(MonitorFrameRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->faceService->processMonitoringFrame($this->auth->id, $data['sessionId'], $data['image'], $data['frameNumber']);

        return $this->success($result);
    }

    public function monitorAudio(MonitorAudioRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->faceService->processAudioEvent($this->auth->id, $data['sessionId'], $data['eventType'], $data['decibel'] ?? null);

        return $this->success($result);
    }

    public function createLivenessSession(LivenessSessionRequest $request): JsonResponse
    {
        $result = $this->faceService->createLivenessSession($this->auth->id, $request->string('purpose')->toString());

        return $this->success($result, 'Liveness session created', 201);
    }

    public function livenessCredentials(): JsonResponse
    {
        return $this->success($this->faceService->getLivenessCredentials());
    }

    public function completeLivenessSession(string $awsSessionId, LivenessCompleteRequest $request): JsonResponse
    {
        $result = $this->faceService->completeLivenessSession($this->auth->id, $awsSessionId, $request->string('purpose')->toString());

        return $this->success($result, 'Liveness session completed');
    }
}

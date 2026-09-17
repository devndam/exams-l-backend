<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Session\StartExamRequest;
use App\Http\Requests\Session\SubmitAnswerRequest;
use App\Services\SessionService;
use App\Support\AuthContext;
use Illuminate\Http\JsonResponse;

class SessionController extends Controller
{
    public function __construct(
        private readonly SessionService $sessionService,
        private readonly AuthContext $auth,
    ) {}

    public function start(int $examTypeId, StartExamRequest $request): JsonResponse
    {
        $result = $this->sessionService->startExam($this->auth->id, $examTypeId, $request->input('liveFaceImage'));

        return $this->success($result, $result['resumed'] ? 'Exam session resumed' : 'Exam started successfully', 201);
    }

    public function currentQuestion(int $sessionId): JsonResponse
    {
        return $this->success($this->sessionService->getCurrentQuestion($sessionId, $this->auth->id));
    }

    public function submitAnswer(int $sessionId, SubmitAnswerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->sessionService->submitAnswer($sessionId, $this->auth->id, $data['questionId'], $data['selectedOption']);

        return $this->success($result, 'Answer submitted');
    }

    public function result(int $sessionId): JsonResponse
    {
        return $this->success($this->sessionService->getSessionResult($sessionId, $this->auth->id));
    }
}

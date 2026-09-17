<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Result\ResultQueryRequest;
use App\Services\ResultService;
use Illuminate\Http\JsonResponse;

class ResultController extends Controller
{
    public function __construct(private readonly ResultService $resultService) {}

    public function index(ResultQueryRequest $request): JsonResponse
    {
        $data = $request->validated();

        return $this->success($this->resultService->getAllResults($data['examTypeId'] ?? null, $data['candidateId'] ?? null));
    }
}

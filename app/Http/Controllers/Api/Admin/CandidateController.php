<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\AssignExamRequest;
use App\Http\Requests\Candidate\CreateCandidateRequest;
use App\Services\CandidateService;
use Illuminate\Http\JsonResponse;

class CandidateController extends Controller
{
    public function __construct(private readonly CandidateService $candidateService) {}

    public function store(CreateCandidateRequest $request): JsonResponse
    {
        $candidate = $this->candidateService->createCandidate($request->validated());

        return $this->success($candidate, 'Candidate created successfully', 201);
    }

    public function index(): JsonResponse
    {
        return $this->success($this->candidateService->getCandidates());
    }

    public function show(int $id): JsonResponse
    {
        return $this->success($this->candidateService->getCandidateById($id));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->candidateService->deleteCandidate($id);

        return $this->success(null, 'Candidate deleted successfully');
    }

    public function assignExam(AssignExamRequest $request): JsonResponse
    {
        $data = $request->validated();
        $assignments = $this->candidateService->assignExam($data['candidateId'], $data['examTypeIds']);

        return $this->success($assignments, 'Exam(s) assigned successfully', 201);
    }

    public function unassignExam(int $id, int $examTypeId): JsonResponse
    {
        $this->candidateService->unassignExam($id, $examTypeId);

        return $this->success(null, 'Exam unassigned successfully');
    }

    public function resetExam(int $id, int $examTypeId): JsonResponse
    {
        $this->candidateService->resetExam($id, $examTypeId);

        return $this->success(null, 'Exam reset successfully');
    }
}

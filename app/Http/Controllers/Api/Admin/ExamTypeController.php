<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\BulkQuestionsRequest;
use App\Http\Requests\Exam\CreateExamTypeRequest;
use App\Http\Requests\Exam\CreateQuestionRequest;
use App\Http\Requests\Exam\UpdateExamTypeRequest;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;

class ExamTypeController extends Controller
{
    public function __construct(private readonly ExamService $examService) {}

    public function store(CreateExamTypeRequest $request): JsonResponse
    {
        return $this->success($this->examService->createExamType($request->validated()), 'Exam type created successfully', 201);
    }

    public function index(): JsonResponse
    {
        return $this->success($this->examService->getExamTypes());
    }

    public function show(int $id): JsonResponse
    {
        return $this->success($this->examService->getExamTypeById($id));
    }

    public function update(int $id, UpdateExamTypeRequest $request): JsonResponse
    {
        return $this->success($this->examService->updateExamType($id, $request->validated()), 'Exam type updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->examService->deleteExamType($id);

        return $this->success(null, 'Exam type deleted successfully');
    }

    public function addQuestion(int $id, CreateQuestionRequest $request): JsonResponse
    {
        return $this->success($this->examService->addQuestion($id, $request->validated()), 'Question added successfully', 201);
    }

    public function addBulkQuestions(int $id, BulkQuestionsRequest $request): JsonResponse
    {
        $count = $this->examService->addBulkQuestions($id, $request->validated());

        return $this->success(['count' => $count], 'Questions added successfully', 201);
    }

    public function questions(int $id): JsonResponse
    {
        return $this->success($this->examService->getQuestions($id));
    }

    public function deleteQuestion(int $id, int $questionId): JsonResponse
    {
        $this->examService->deleteQuestion($questionId);

        return $this->success(null, 'Question deleted successfully');
    }
}

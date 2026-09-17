<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\ExamType;
use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;

class ExamService
{
    public function createExamType(array $data): ExamType
    {
        if (ExamType::query()->where('name', $data['name'])->exists()) {
            throw new ApiException(409, 'An exam type with this name already exists');
        }

        return ExamType::query()->create($data);
    }

    public function getExamTypes(): Collection
    {
        return ExamType::query()->withCount(['questions', 'assignments'])->orderByDesc('created_at')->get();
    }

    public function getExamTypeById(int $id): ExamType
    {
        $examType = ExamType::query()->withCount('questions')->find($id);
        if (! $examType) {
            throw new ApiException(404, 'Exam type not found');
        }

        return $examType;
    }

    public function addQuestion(int $examTypeId, array $data): Question
    {
        $this->getExamTypeById($examTypeId);

        return Question::query()->create([...$data, 'exam_type_id' => $examTypeId]);
    }

    /** @param array<int, array<string, mixed>> $questions */
    public function addBulkQuestions(int $examTypeId, array $questions): int
    {
        $this->getExamTypeById($examTypeId);

        $rows = array_map(fn ($q) => [...$q, 'exam_type_id' => $examTypeId, 'created_at' => now()], $questions);
        Question::query()->insert($rows);

        return count($rows);
    }

    public function getQuestions(int $examTypeId): Collection
    {
        $this->getExamTypeById($examTypeId);

        return Question::query()->where('exam_type_id', $examTypeId)->orderByDesc('created_at')->get();
    }

    public function updateExamType(int $id, array $data): ExamType
    {
        $examType = $this->getExamTypeById($id);

        if (! empty($data['name'])) {
            $exists = ExamType::query()->where('name', $data['name'])->where('id', '!=', $id)->exists();
            if ($exists) {
                throw new ApiException(409, 'An exam type with this name already exists');
            }
        }

        $examType->update($data);

        return $examType;
    }

    public function deleteQuestion(int $questionId): void
    {
        $question = Question::query()->find($questionId);
        if (! $question) {
            throw new ApiException(404, 'Question not found');
        }

        $question->delete();
    }

    public function deleteExamType(int $id): void
    {
        $this->getExamTypeById($id)->delete();
    }
}

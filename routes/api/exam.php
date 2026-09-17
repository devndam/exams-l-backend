<?php

use App\Http\Controllers\Api\Admin\ExamTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/exam-types')->middleware(['auth.jwt', 'role:admin'])->group(function () {
    Route::post('/', [ExamTypeController::class, 'store']);
    Route::get('/', [ExamTypeController::class, 'index']);
    Route::get('/{id}', [ExamTypeController::class, 'show'])->whereNumber('id');
    Route::put('/{id}', [ExamTypeController::class, 'update'])->whereNumber('id');
    Route::delete('/{id}', [ExamTypeController::class, 'destroy'])->whereNumber('id');

    Route::post('/{id}/questions', [ExamTypeController::class, 'addQuestion'])->whereNumber('id');
    Route::post('/{id}/questions/bulk', [ExamTypeController::class, 'addBulkQuestions'])->whereNumber('id');
    Route::get('/{id}/questions', [ExamTypeController::class, 'questions'])->whereNumber('id');
    Route::delete('/{id}/questions/{questionId}', [ExamTypeController::class, 'deleteQuestion'])->whereNumber('id')->whereNumber('questionId');
});

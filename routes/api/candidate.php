<?php

use App\Http\Controllers\Api\Admin\CandidateController;
use App\Http\Controllers\Api\CandidateSelfController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/candidates')->middleware(['auth.jwt', 'role:admin'])->group(function () {
    Route::post('/', [CandidateController::class, 'store']);
    Route::get('/', [CandidateController::class, 'index']);
    Route::post('/assign-exam', [CandidateController::class, 'assignExam']);
    Route::delete('/{id}/unassign/{examTypeId}', [CandidateController::class, 'unassignExam'])->whereNumber(['id', 'examTypeId']);
    Route::put('/{id}/reset/{examTypeId}', [CandidateController::class, 'resetExam'])->whereNumber(['id', 'examTypeId']);
    Route::delete('/{id}', [CandidateController::class, 'destroy'])->whereNumber('id');
    Route::get('/{id}', [CandidateController::class, 'show'])->whereNumber('id');
});

Route::prefix('candidate')->middleware(['auth.jwt', 'role:candidate'])->group(function () {
    Route::get('/my/exams', [CandidateSelfController::class, 'myExams']);
    Route::put('/my/certificate', [CandidateSelfController::class, 'updateCertificate']);
});

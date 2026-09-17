<?php

use App\Http\Controllers\Api\Admin\SessionController as AdminSessionController;
use App\Http\Controllers\Api\SessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('candidate/session')->middleware(['auth.jwt', 'role:candidate', 'throttle:exam'])->group(function () {
    Route::post('/start/{examTypeId}', [SessionController::class, 'start'])->whereNumber('examTypeId');
    Route::get('/{sessionId}/question', [SessionController::class, 'currentQuestion'])->whereNumber('sessionId');
    Route::post('/{sessionId}/answer', [SessionController::class, 'submitAnswer'])->whereNumber('sessionId');
    Route::get('/{sessionId}/result', [SessionController::class, 'result'])->whereNumber('sessionId');
});

Route::prefix('admin/sessions')->middleware(['auth.jwt', 'role:admin'])->group(function () {
    Route::get('/active', [AdminSessionController::class, 'active']);
});

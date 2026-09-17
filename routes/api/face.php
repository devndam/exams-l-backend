<?php

use App\Http\Controllers\Api\Admin\FaceController as AdminFaceController;
use App\Http\Controllers\Api\FaceController;
use Illuminate\Support\Facades\Route;

// ─── Candidate: enrollment & verification ──────────────────────────────────

Route::prefix('face')->middleware(['auth.jwt', 'role:candidate'])->group(function () {
    Route::post('/enroll', [FaceController::class, 'enroll'])->middleware('throttle:face');
    Route::post('/verify', [FaceController::class, 'verify'])->middleware('throttle:face');
    Route::get('/status', [FaceController::class, 'status']);
    Route::get('/reference', [FaceController::class, 'reference'])->middleware('throttle:face');
    Route::post('/monitor', [FaceController::class, 'monitor'])->middleware('throttle:monitoring');
    Route::post('/monitor/audio', [FaceController::class, 'monitorAudio'])->middleware('throttle:monitoring');

    // ─── Candidate: liveness ────────────────────────────────────────────────
    Route::post('/liveness/session', [FaceController::class, 'createLivenessSession'])->middleware('throttle:face');
    Route::get('/liveness/credentials', [FaceController::class, 'livenessCredentials']);
    Route::post('/liveness/session/{awsSessionId}/complete', [FaceController::class, 'completeLivenessSession'])->middleware('throttle:face');
});

// ─── Admin ──────────────────────────────────────────────────────────────────

Route::prefix('face')->middleware(['auth.jwt', 'role:admin'])->group(function () {
    Route::get('/enrollments/pending', [AdminFaceController::class, 'pendingEnrollments']);
    Route::get('/enrollments/{candidateId}/image', [AdminFaceController::class, 'captureImage'])->whereNumber('candidateId');
    Route::put('/enrollments/{candidateId}/review', [AdminFaceController::class, 'reviewEnrollment'])->whereNumber('candidateId');
    Route::put('/enrollments/{candidateId}/reset', [AdminFaceController::class, 'resetEnrollment'])->whereNumber('candidateId');
    Route::get('/flagged', [AdminFaceController::class, 'flagged']);
    Route::get('/monitoring/{sessionId}', [AdminFaceController::class, 'monitoringEvents'])->whereNumber('sessionId');
    Route::get('/liveness/sessions', [AdminFaceController::class, 'livenessSessions']);
});

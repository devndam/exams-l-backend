<?php

use App\Http\Controllers\DeployController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['success' => true, 'message' => 'API is running']));

Route::post('/deploy/callback', [DeployController::class, 'handle']);

require __DIR__.'/api/auth.php';
require __DIR__.'/api/exam.php';
require __DIR__.'/api/candidate.php';
require __DIR__.'/api/result.php';
require __DIR__.'/api/session.php';
require __DIR__.'/api/face.php';

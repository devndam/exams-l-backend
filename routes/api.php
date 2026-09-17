<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['success' => true, 'message' => 'API is running']));

require __DIR__.'/api/auth.php';
require __DIR__.'/api/exam.php';
require __DIR__.'/api/candidate.php';
require __DIR__.'/api/result.php';
require __DIR__.'/api/session.php';
require __DIR__.'/api/face.php';

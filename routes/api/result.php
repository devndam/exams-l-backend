<?php

use App\Http\Controllers\Api\Admin\ResultController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/results', [ResultController::class, 'index'])->middleware(['auth.jwt', 'role:admin']);

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LessonController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::prefix('lessons')->group(function () {
    Route::get('/recent', [LessonController::class, 'recent']);
    Route::get('/{idCours}', [LessonController::class, 'show'])->whereNumber('idCours');

    Route::middleware('admin.role')->post('/', [LessonController::class, 'store']);
});

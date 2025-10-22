<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\PaymentController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware(['jwt.auth'])->group(function () {

    Route::get('/profile', [AuthController::class, 'getProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('forms')->group(function () {
        Route::get('/index', [FormController::class, 'index']);
        Route::get('/show', [FormController::class, 'show']);
        Route::post('/store', [FormController::class, 'store']); 
        Route::put('/update', [FormController::class, 'update']); 
        Route::delete('/destroy', [FormController::class, 'destroy']); 
    });

    Route::prefix('submissions')->group(function () {
        Route::get('/', [SubmissionController::class, 'index']);
        Route::post('/store', [SubmissionController::class, 'store']); 
        Route::get('/show', [SubmissionController::class, 'show']);
        Route::put('/status', [SubmissionController::class, 'updateStatus']);
        Route::post('/upload', [SubmissionController::class, 'upload']);
        Route::delete('/destroy', [SubmissionController::class, 'destroy']); 
    });
    Route::prefix('payments')->group(function () {
        Route::post('/initiate/{submission}', [PaymentController::class, 'initiate']); 
        Route::get('/', [PaymentController::class, 'index']); 
        Route::get('/{submission}/receipt', [PaymentController::class, 'downloadReceipt'])->name('payments.receipt');
    });
});
Route::get('/payments/success/{submission}', [PaymentController::class, 'success'])->name('payments.success');
Route::get('/payments/cancel/{submission}', [PaymentController::class, 'cancel'])->name('payments.cancel');

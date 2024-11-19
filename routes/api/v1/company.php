<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Company\CompanyController;

Route::middleware(['auth:api', 'role:superadmin'])->group(function () {
    Route::get('/', [CompanyController::class, 'index']);
    Route::post('/', [CompanyController::class, 'store']);

    Route::prefix('{company}')->group(function () {
        Route::get('/', [CompanyController::class, 'show']);
        Route::put('/', [CompanyController::class, 'update']);
        Route::delete('/', [CompanyController::class, 'destroy']);
    });
});

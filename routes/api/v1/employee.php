<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Employee\EmployeeController;

Route::middleware(['auth:api'])->group(function () {
    Route::get('/', [EmployeeController::class, 'index'])->middleware('role:superadmin,manager,employee');
    Route::post('/', [EmployeeController::class, 'store'])->middleware('role:manager');

    Route::prefix('{employee}')->group(function () {
        Route::get('/', [EmployeeController::class, 'show'])->middleware('role:superadmin,manager,employee');
        Route::put('/', [EmployeeController::class, 'update'])->middleware('role:manager');
        Route::delete('/', [EmployeeController::class, 'destroy'])->middleware('role:manager');
    });
});

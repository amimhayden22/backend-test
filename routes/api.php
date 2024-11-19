<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(base_path('routes/api/v1/auth.php'));
    Route::prefix('companies')->group(base_path('routes/api/v1/company.php'));
    Route::prefix('employees')->group(base_path('routes/api/v1/employee.php'));
    Route::prefix('users')->group(base_path('routes/api/v1/user.php'));
});

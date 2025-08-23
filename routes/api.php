<?php

use App\Constants\UserRole;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public Authentication Route
    Route::post('/auth/login', [AuthController::class, 'login']);


    // All routes in this group are protected by the 'auth:sanctum' middleware
    Route::middleware('auth:sanctum')->group(function () {
        /*
        |----------------------------------------------------------------------
        | User Routes
        |----------------------------------------------------------------------
        */
        Route::controller(UserController::class)->prefix('users')->group(function () {
            Route::get('/profile', 'profile');

            // Define the resource routes manually to apply specific middleware
            Route::get('/', 'index');
            Route::get('/{user}', 'show');
            Route::patch('/{user}', 'update');
            Route::delete('/{user}', 'destroy');

            // Apply the role middleware ONLY to the 'store' action.
            Route::post('/', 'store')->middleware('role:' . UserRole::ADMIN);
        });



        /*
        |----------------------------------------------------------------------
        | Building Routes
        |----------------------------------------------------------------------
        */
        Route::controller(BuildingController::class)->prefix('buildings')->group(function () {
            // Import-related routes
            Route::get('/import/template', 'generateImportTemplate')
                ->middleware('role:' . UserRole::ADMIN . ',' . UserRole::MANAGER . ',' . UserRole::DAMAGE_SOLVER);
            Route::post('/import', 'import')
                ->middleware('role:' . UserRole::ADMIN . ',' . UserRole::MANAGER . ',' . UserRole::DAMAGE_SOLVER);

            // Publicly accessible GET routes
            Route::get('/', 'index');
            Route::get('/{building:uuid}', 'show');
            Route::get('/{building:id}/notifiers', 'notifiers');

            // Role-protected POST, PATCH, DELETE routes
            Route::post('/', 'store')
                ->middleware('role:' . UserRole::ADMIN . ',' . UserRole::DAMAGE_SOLVER);

            Route::patch('/{building:uuid}', 'update')
                ->middleware('role:' . UserRole::ADMIN . ',' . UserRole::DAMAGE_SOLVER . ',' . UserRole::MANAGER);

            Route::delete('/{building:uuid}', 'destroy')
                ->middleware('role:' . UserRole::ADMIN);
        });


        /*
        |----------------------------------------------------------------------
        | Report Routes
        |----------------------------------------------------------------------
        */
        Route::controller(ReportController::class)->prefix('reports')->group(function () {
            // Publicly accessible GET routes
            Route::get('/', 'index');
            Route::get('/{report:uuid}', 'show');

            // Role-protected POST, PATCH routes
            Route::post('/', 'store')
                ->middleware('role:' . UserRole::ADMIN . ',' . UserRole::DAMAGE_SOLVER . ',' . UserRole::MANAGER . ',' . UserRole::CUSTOMER);

            Route::patch('/{report:uuid}', 'update')
                ->middleware('role:' . UserRole::ADMIN . ',' . UserRole::DAMAGE_SOLVER . ',' . UserRole::MANAGER);

            // This route is protected by sanctum, but has no specific role middleware
            Route::post('/{report:uuid}/attachments', 'uploadAttachments');
        });
    });
});

<?php

use App\Constants\UserRole;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\DocumentRequestPublicController;
use App\Http\Controllers\Api\InsurerController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public Authentication Route
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::controller(DocumentRequestPublicController::class)
        ->prefix('public/document-requests')
        ->group(function () {
            Route::get('/{documentRequest:uuid}', 'show');
            Route::post('/{documentRequest:uuid}/items/{documentRequestItem:uuid}/files', 'storeItemFile');
        });

    // All routes in this group are protected by the 'auth:sanctum' middleware
    Route::middleware('auth:sanctum')->group(function () {
        $role = fn(...$roles) => 'role:' . implode(',', $roles);

        $adminOnly = $role(UserRole::ADMIN);
        $adminAndSolver = $role(UserRole::ADMIN, UserRole::DAMAGE_SOLVER);
        $adminManagerSolver = $role(UserRole::ADMIN, UserRole::MANAGER, UserRole::DAMAGE_SOLVER);
        $reportCreateRoles = $role(UserRole::ADMIN, UserRole::DAMAGE_SOLVER, UserRole::MANAGER, UserRole::CUSTOMER);
        $reportManageRoles = $role(UserRole::ADMIN, UserRole::DAMAGE_SOLVER, UserRole::MANAGER);
        /*
        |----------------------------------------------------------------------
        | User Routes
        |----------------------------------------------------------------------
        */
        Route::controller(UserController::class)->prefix('users')->group(function () use ($adminOnly) {
            Route::get('/profile', 'getProfile');

            // Define the resource routes manually to apply specific middleware
            Route::get('/', 'index');
            Route::get('/{user}', 'show');
            Route::patch('/{user}', 'update');
            Route::delete('/{user}', 'destroy');

            // Apply the role middleware ONLY to the 'store' action.
            Route::post('/', 'store')->middleware($adminOnly);
        });

        /*
        |----------------------------------------------------------------------
        | Building Routes
        |----------------------------------------------------------------------
        */
        Route::controller(BuildingController::class)->prefix('buildings')->group(function () use (
            $adminOnly,
            $adminAndSolver,
            $adminManagerSolver
        ) {
            // Import-related routes
            Route::middleware($adminManagerSolver)->group(function () {
                Route::get('/import/template', 'generateImportTemplate');
                Route::post('/import', 'import');
            });

            // Get reports for building
            Route::get('/{building}/reports', 'reports');

            // Publicly accessible GET routes
            Route::get('/', 'index');
            Route::get('/{building}', 'show');
            Route::get('/{building}/notifiers', 'notifiers');

            // Role-protected POST, PATCH, DELETE routes
            Route::post('/', 'store')->middleware($adminAndSolver);
            Route::patch('/{building}', 'update')->middleware($adminManagerSolver);
            Route::delete('/{building}', 'destroy')->middleware($adminOnly);
        });

        /*
        |--------------------------------------------------------------------------
        | Insurer Routes
        |--------------------------------------------------------------------------
        */
        Route::controller(InsurerController::class)->prefix('insurers')->group(function () use ($adminAndSolver) {
            Route::get('/', 'index');
            Route::get('/{insurer}', 'show');
            Route::post('/', 'store')->middleware($adminAndSolver);
            Route::patch('/{insurer}', 'update')->middleware($adminAndSolver);
            Route::delete('/{insurer}', 'destroy')->middleware($adminAndSolver);
        });

        /*
        |----------------------------------------------------------------------
        | Report Routes
        |----------------------------------------------------------------------
        */
        Route::controller(ReportController::class)->prefix('reports')->group(function () use (
            $reportCreateRoles,
            $reportManageRoles
        ) {
            // Publicly accessible GET routes
            Route::get('/', 'index');
            Route::get('/{report}', 'show');

            // Role-protected POST, PATCH routes
            Route::post('/', 'store')->middleware($reportCreateRoles);
            Route::patch('/{report}', 'update')->middleware($reportManageRoles);

            // This route is protected by sanctum, but has no specific role middleware
            Route::post('/{report}/attachments', 'uploadAttachments');
            Route::post('/{report}/status', 'changeStatus')->middleware($reportManageRoles);
            Route::patch('/{report}/damage-id', 'updateDamageId')->middleware($reportManageRoles);
        });
    });
});

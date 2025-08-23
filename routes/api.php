<?php

use App\Constants\UserRole;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['prefix' => 'v1'], function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::get('/users/profile', [UserController::class, 'getProfile']);
        Route::apiResource('/users', UserController::class);
    });

    // Route for the custom "notifiers" action. We bind by ID here.
    Route::get('/buildings/{building:id}/notifiers', [BuildingController::class, 'notifiers']);

    // Standard API resource routes, but with middleware applied to specific actions.
    Route::post('/buildings', [BuildingController::class, 'store'])
        ->middleware('role:' . UserRole::ADMIN . ',' . UserRole::DAMAGE_SOLVER);

    Route::patch('/buildings/{building:uuid}', [BuildingController::class, 'update'])
        ->middleware('role:' . UserRole::ADMIN . ',' . UserRole::DAMAGE_SOLVER . ',' . UserRole::MANAGER);

    Route::delete('/buildings/{building:uuid}', [BuildingController::class, 'destroy'])
        ->middleware('role:' . UserRole::ADMIN);

    // Routes that don't need special role middleware can be defined normally.
    Route::get('/buildings', [BuildingController::class, 'index']);
    Route::get('/buildings/{building:uuid}', [BuildingController::class, 'show']);

    // Create Report
    Route::post('/reports', [ReportController::class, 'store'])
        ->middleware('role:' . UserRole::ADMIN . ',' . UserRole::DAMAGE_SOLVER . ',' . UserRole::MANAGER . ',' . UserRole::CUSTOMER);

    // List Reports (all roles can view, but service will scope results)
    Route::get('/reports', [ReportController::class, 'index']);

    // Get a specific report
    Route::get('/reports/{report:uuid}', [ReportController::class, 'show']);

    // Update a Report
    Route::patch('/reports/{report:uuid}', [ReportController::class, 'update'])
        ->middleware('role:' . UserRole::ADMIN . ',' . UserRole::DAMAGE_SOLVER . ',' . UserRole::MANAGER);

    // Upload attachments for a report
    Route::post('/reports/{report:uuid}/attachments', [ReportController::class, 'uploadAttachments']);
});

<?php

use App\Http\Controllers\Api\MockInsurerController;
use App\Http\Controllers\Api\PolicyRequestController;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PolicyFlow REST API Routes
|--------------------------------------------------------------------------
*/

// Mandatory Scope Endpoints
Route::prefix('policy-requests')->group(function () {
    Route::post('/', [PolicyRequestController::class, 'store']);
    Route::get('/', [PolicyRequestController::class, 'index']);
    Route::post('/reconcile-all', [PolicyRequestController::class, 'reconcileAllStale']);
    Route::get('/{id}', [PolicyRequestController::class, 'show']);
    Route::post('/{id}/submit', [PolicyRequestController::class, 'submit']);
    Route::post('/{id}/reconcile', [PolicyRequestController::class, 'reconcile']);
});

// Mock Insurer Simulation Endpoints
Route::prefix('mock-insurer')->group(function () {
    Route::post('/issue', [MockInsurerController::class, 'issue']);
    Route::get('/status/{provider_reference}', [MockInsurerController::class, 'status']);
    Route::post('/callback', [MockInsurerController::class, 'callback']);
    Route::post('/simulate-duplicate-callback', [MockInsurerController::class, 'simulateDuplicateCallback']);
});

// Dynamic Live Pricing Calculator Preview Endpoint (for frontend live form updates)
Route::post('/pricing/preview', function (Request $request, PricingService $pricingService) {
    try {
        $validated = $request->validate([
            'plan_code' => 'required|string',
            'sum_insured' => 'required|integer',
            'destination' => 'required|string',
            'date_of_birth' => 'required|date_format:Y-m-d',
            'trip_start' => 'required|date_format:Y-m-d',
            'trip_end' => 'required|date_format:Y-m-d',
        ]);

        $calculation = $pricingService->calculate(
            planCode: $validated['plan_code'],
            sumInsured: (int) $validated['sum_insured'],
            destination: $validated['destination'],
            dateOfBirth: $validated['date_of_birth'],
            tripStart: $validated['trip_start'],
            tripEnd: $validated['trip_end']
        );

        return response()->json([
            'success' => true,
            'data' => $calculation,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
});

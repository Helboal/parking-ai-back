<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BranchDiscountController;
use App\Http\Controllers\Admin\BranchFlatRateController;
use App\Http\Controllers\Admin\BranchParkingCapacityController;
use App\Http\Controllers\Admin\BranchRateController;
use App\Http\Controllers\Admin\BranchTaxController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\EntryController;
use App\Http\Controllers\Admin\EntryTypeController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SubscriptionTypeController;
use App\Http\Controllers\Admin\TaxController;
use App\Http\Controllers\Admin\UserBranchController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\VehicleTypeController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::apiResource('document-types', DocumentTypeController::class);
        Route::apiResource('users', UserController::class);
        Route::apiResource('branches', BranchController::class);
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('vehicles', VehicleController::class);
        Route::apiResource('vehicle-types', VehicleTypeController::class);
        Route::apiResource('entry-types', EntryTypeController::class);
        Route::apiResource('subscription-types', SubscriptionTypeController::class);
        Route::apiResource('payment-methods', PaymentMethodController::class);
        Route::apiResource('taxes', TaxController::class);
        Route::apiResource('branch-parking-capacity', BranchParkingCapacityController::class);
        Route::apiResource('branch-rates', BranchRateController::class);
        Route::apiResource('branch-discounts', BranchDiscountController::class);
        Route::apiResource('branch-flat-rates', BranchFlatRateController::class);
        Route::apiResource('branch-taxes', BranchTaxController::class);
        Route::apiResource('user-branches', UserBranchController::class);
        Route::apiResource('subscriptions', SubscriptionController::class);
        Route::apiResource('entries', EntryController::class);
        Route::apiResource('invoices', InvoiceController::class);
    });
});

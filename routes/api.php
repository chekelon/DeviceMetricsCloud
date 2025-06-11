<?php

use App\Http\Controllers\RegionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\ReadingController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');




Route::post('register', [\App\Http\Controllers\AuthController::class, 'register']);

Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    //Regions routes
    Route::get('regiones',[RegionController::class, 'index']);
    //Locations routes
    Route::get('locations', [LocationController::class, 'index']);
    Route::post('locations', [LocationController::class, 'store']);
    Route::get('locations/{id}', [LocationController::class, 'show']);
    //Sensors routes
    Route::post('sensors', [SensorController::class, 'store']);
    Route::get('sensors/{id}', [SensorController::class, 'show']);
    //Readings routes
    Route::post('readings', [ReadingController::class, 'store']);
    //Logout routes
    Route::get('logout', [AuthController::class, 'logout']);
});

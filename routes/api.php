<?php

use App\Events\ChatMessageSent;
use App\Http\Controllers\RegionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\ReadingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PusherAuthController;
use App\Events\StatusConnectionDevice as Event;
use App\Events\StatusConnectionDevice;
use Illuminate\Support\Facades\Auth;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');




Route::post('register', [\App\Http\Controllers\AuthController::class, 'register']);

Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);


// Ruta de prueba para disparar el evento
Route::get('/send-test-event', function () {
    event(new StatusConnectionDevice());
    return "Evento de prueba enviado!";
});

Route::get('/status-connection-device', function () {
    // ... en tu controlador o servicio
        $user = Auth::user(); // O el usuario que envía el mensaje
        $message = "Hola a todos en la sala!";
        $roomId = 1; // ID de la sala de chat
    event(new ChatMessageSent($user,$message, $roomId));
    return "Evento de conexión de dispositivo enviado!";
})->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
    //User routes
    Route::post('user/fcm-token', [UserController::class, 'storeFCMToken']);
    Route::get('users', [UserController::class, 'index']);

    //Regions routes
    Route::get('regiones',[RegionController::class, 'index']);
    Route::get('regiones/{id}', [RegionController::class, 'show']);
    //Locations routes
    Route::get('locations', [LocationController::class, 'index']);
    Route::post('locations', [LocationController::class, 'store']);
    Route::get('locations/{id}', [LocationController::class, 'show']);
    //Sensors routes
    Route::post('sensors', [SensorController::class, 'store']);
    Route::get('sensors/{id}', [SensorController::class, 'show']);
    Route::get('sensors/{id}/show',[SensorController::class,'showById']);
    Route::post('search/sensor', [SensorController::class, 'searchByName']);
    Route::post('sensors/{id}/history', [SensorController::class, 'history']);
    Route::post('sensors/{id}/update',[SensorController::class,'update']);
    //Readings routes
    Route::post('readings', [ReadingController::class, 'store']);
    //Notifications routes
    Route::get('notifications/sensor/{id}', [NotificationController::class, 'show']);
    //Logout routes
    Route::get('logout', [AuthController::class, 'logout']);
});

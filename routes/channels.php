<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Sensor; // Importa tu modelo Sensor
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // Importa la clase Auth para verificar autenticación

Log::info('--- INICIO LOG AUTH BROADCAST ---');
Log::info('Request Channel Name: ' . request()->input('channel_name'));
Log::info('Request Socket ID: ' . request()->input('socket_id'));

// Agrega esta línea para ver el encabezado Authorization
Log::info('Authorization Header Received: ' . request()->header('Authorization'));

Log::info('Auth::check(): ' . (Auth::check() ? 'TRUE' : 'FALSE'));

if (Auth::check()) {
    Log::info('Authenticated User ID: ' . Auth::user()->id);
    Log::info('Authenticated User Name: ' . Auth::user()->name);
} else {
    Log::info('Usuario NO autenticado en Laravel para broadcasting.');
}



Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    Log::info('Intentando autenticar App.Models.User.' . $id . ' - Usuario actual: ' . ($user ? $user->id : 'null'));
    return (int) $user->id === (int) $id;
});

Broadcast::channel('grupo', function ($user) {
    // Aquí puedes añadir lógica para verificar si el usuario tiene permiso para unirse a este chat.
    // Por ejemplo, verificar si el usuario es miembro de la sala.
    Log::info('--- INICIO AUTORIZACION CANAL CHAT ---');
    Log::info('Canal de Request: ' . request()->input('channel_name')); // Verifica el nombre completo que Laravel recibe
    Log::info('Usuario Autenticado ID: ' . ($user ? $user->id : 'null'));
    if(!$user){
        Log::warning('Denegado a canal chat.: Usuario no autenticado.');
        return false; // Deniega la suscripción si no hay usuario autenticado.
    }

    // 2. Buscar el sensor en la base de datos
    $sensor = $user->sensor; // Asumiendo que el usuario tiene un sensor asociado

    // 3. Lógica de Autorización (y devolver datos del miembro)
    if ($sensor) {
        // Preparar los datos del miembro para el canal de presencia
        // Estos datos serán visibles para otros miembros del canal.
        $memberData = [
            'id' => (string)$sensor->id,
            'name' => $sensor->name,
            'location' => $sensor->location->name,
        ];


        return $memberData;
    }else{
        $memberData = [
            'id' => "0",
            'name' => $user->name,
            'location' => 'personal',
        ];


        return $memberData;
    }
    // Si el usuario está autorizado, debes devolver un array con los datos del usuario que quieres compartir.
    // Estos datos serán visibles para otros usuarios en el canal de presencia.
    return false;
});


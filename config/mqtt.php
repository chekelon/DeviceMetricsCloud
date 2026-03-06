<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Broker MQTT
    |--------------------------------------------------------------------------
    |
    | Aquí defines los parámetros de conexión para el cliente MQTT. Estos
    | valores se extraen del archivo .env para mantener la seguridad.
    |
    */

    'host'      => env('MQTT_HOST', '127.0.0.1'),
    'port'      => (int) env('MQTT_PORT', 1883),
    'user'      => env('MQTT_USER'),
    'password'  => env('MQTT_PASSWORD'),
    'client_id' => env('MQTT_CLIENT_ID', 'laravel_backend'),
    'topic'     => env('MQTT_TOPIC', 'test/#'),
    
    // Opciones adicionales útiles para el cliente PhpMqtt
    'keep_alive' => 60,
];
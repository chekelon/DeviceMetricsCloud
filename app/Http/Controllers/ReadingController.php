<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Contract\Messaging;


class ReadingController extends Controller
{
    protected $messaging;

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sensor_id' => 'required|integer|exists:sensors,id',
            'value' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }


        $sensor = \App\Models\Sensor::findOrFail($request->sensor_id);

        $valueResult = (int) ($request->value - $sensor->min_value) / ($sensor->max_value - $sensor->min_value) * 100;
        $valuePorcentReal = 100 -$valueResult;

        // Asegurarse de que el valor esté dentro del rango permitido
        if ($valuePorcentReal < 0 || $valuePorcentReal > 100) {
            return response()->json(['error' => 'Value must be between 0 and 100'], 422);
        }

        $user = $request->user();
        $this->messaging = app(Messaging::class);

        $this->sendPushNotification($user->fcm_token,"Ubicacion Jardin","Nivel de agua bajo $valuePorcentReal %",["sensor_id" => $request->sensor_id, "value" => $valuePorcentReal]);
        

        $reading = \App\Models\Reading::create([
            'sensor_id' => $request->sensor_id,
            'value' => $valuePorcentReal,
            
        ]);

        return response()->json($reading, 201);
    }


    public function sendPushNotification($deviceToken,$title,$body,$data)
    {

        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::new('token', $deviceToken)
                ->withNotification($notification)
                ->withAndroidConfig([
                    'priority' => 'high',
                    'ttl' => '3600s', // Tiempo de vida del mensaje
                ])->withData($data)
                ->toToken($deviceToken);

            $this->messaging->send($message);

            return response()->json(['message' => 'Notificación enviada con éxito.'], 200);

        } catch (\Throwable $e) {
            // Puedes registrar el error para depuración
            
            Log::error("Error al enviar notificación FCM: " . $e->getMessage(), ['token' => $deviceToken]);
            return response()->json(['error' => 'Error al enviar la notificación: ' . $e->getMessage()], 500);
        }
    }

    // Método de ejemplo para enviar a múltiples tokens (a menudo desde la DB)
    public function sendNotificationsToMultipleUsers(Request $request)
    {
        // Asume que tienes una tabla `users` con una columna `fcm_token`
        // $deviceTokens = User::whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
        $deviceTokens = [
            'TOKEN_DEL_USUARIO_1',
            'TOKEN_DEL_USUARIO_2',
            // ... más tokens
        ];

        if (empty($deviceTokens)) {
            return response()->json(['message' => 'No hay tokens de dispositivos para enviar.'], 404);
        }

        $title = $request->input('title', 'Notificación General');
        $body = $request->input('body', 'Este es un mensaje para todos tus usuarios.');
        $data = $request->input('data', []);

        try {
            $notification = Notification::create($title, $body);

            $messages = [];
            foreach ($deviceTokens as $token) {
                $messages[] = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification)
                    ->withData($data);
            }

            // Envía múltiples mensajes en un solo lote
            $this->messaging->sendAll($messages);

            return response()->json(['message' => 'Notificaciones enviadas a múltiples usuarios.'], 200);

        } catch (\Throwable $e) {
            Log::error("Error al enviar notificaciones FCM a múltiples usuarios: " . $e->getMessage());
            return response()->json(['error' => 'Error al enviar notificaciones: ' . $e->getMessage()], 500);
        }
    }


}

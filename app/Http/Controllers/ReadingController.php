<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\AndroidConfig;


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

        
        $this->messaging = app(Messaging::class);
        $lastNotification = \App\Models\Notification::where('sensor_id', $sensor->id)
            ->orderBy('created_at', 'desc')
            ->first();

        Log::info('Última notificación: ' . ($lastNotification ? $lastNotification->created_at : 'No hay notificaciones previas'));
        
        if($lastNotification){
            $hoursDiff = abs(now()->diffInHours($lastNotification->created_at));
            Log::info('' . $hoursDiff.' >  '.$sensor->alert_notification_interval);
            if ( $hoursDiff > $sensor->alert_notification_interval) {
                $this->sendNotification($sensor, $valuePorcentReal, $request);
            }
        }else{
            Log::info('No hubo notificaciones previas');
            $this->sendNotification($sensor, $valuePorcentReal, $request);
        }
        
        $reading = \App\Models\Reading::create([
            'sensor_id' => $request->sensor_id,
            'value' => $valuePorcentReal,
            
        ]);

        return response()->json($reading, 201);
    }


    
    /**
     * Envia una notificación al usuario si el valor del sensor supera los límites establecidos.
     *
     * @param Sensor $sensor
     * @param int $valuePorcentReal
     * @param Request $request
     */
    public function sendNotification(Sensor $sensor,int $valuePorcentReal,Request $request)
    {

        $user = $request->user();
        if( $valuePorcentReal >= $sensor->alert_max_value   || $valuePorcentReal <= $sensor->alert_min_value || ($valuePorcentReal > 60 && $valuePorcentReal < $sensor->alert_max_value)){

            Log::info('Enviando notificación al usuario: ' . $user->name . ' para el sensor: ' . $sensor->id. ' con valor: ' . $valuePorcentReal);
            $notification = $this->getContentAlert($valuePorcentReal, $sensor);
            //$this->sendPushNotification($user->fcm_token,$notification["title"],$notification["body"],["sensor_id" => $request->sensor_id,"type" => $notification["type"]]);
            $this->sendNotificationsToMultipleUsers( $request,$valuePorcentReal, $sensor);

            \App\Models\Notification::create([
            'sensor_id' => $sensor->id,
            'title' => $notification["title"],
            'body' => $notification["body"],
            'type'=> $notification["type"],
            ]);
        }else{
            Log::info('No se envió notificación al usuario: ' . $user->name . ' para el sensor: ' . $sensor->id. ' con valor: ' . $valuePorcentReal);
        }
    }

    public function sendPushNotification($deviceToken,$title,$body,$data)
    {

        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::new('token', $deviceToken)
                ->withNotification($notification)
                ->withAndroidConfig(
                    AndroidConfig::new()->withHighMessagePriority() // Tiempo de vida del mensaje
                )->withHighestPossiblePriority()
                ->withData($data)
                ->toToken($deviceToken);

            $this->messaging->send($message);


            Log::info("Notificación enviada con éxito a: " . $deviceToken);

        } catch (\Throwable $e) {
            // Puedes registrar el error para depuración
            
            Log::error("Error al enviar notificación FCM: " . $e->getMessage(), ['token' => $deviceToken]);
            return response()->json(['error' => 'Error al enviar la notificación: ' . $e->getMessage()], 500);
        }
    }

    // Método de ejemplo para enviar a múltiples tokens (a menudo desde la DB)
    public function sendNotificationsToMultipleUsers(Request $request,$valuePorcentReal,Sensor $sensor)
    {
        // Asume que tienes una tabla `users` con una columna `fcm_token`
        $user = $request->user();
        $region = $user->region_id;
        $deviceTokens = \App\Models\User::where('region_id', $region)->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
        // $deviceTokens = User::whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
        /*$deviceTokens = [
            'TOKEN_DEL_USUARIO_1',
            'TOKEN_DEL_USUARIO_2',
            // ... más tokens
        ];*/

        if (empty($deviceTokens)) {
            Log::error("Error no hay tokens para enviar notificaciones: ", ['tokens' => $deviceTokens]);
            return response()->json(['message' => 'No hay tokens de dispositivos para enviar.'], 404);
        }

        $notificationContent = $this->getContentAlert($valuePorcentReal, $sensor);

        try {
            $notification = Notification::create($notificationContent['title'], $notificationContent['body']);

            $messages = [];
            
            foreach ($deviceTokens as $token) {
                $messages[] = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification)
                    ->withAndroidConfig(
                    AndroidConfig::new()->withHighMessagePriority()->withPublicNotificationVisibility())
                    ->withData(["sensor_id" => $sensor->id,"type" => $notificationContent["type"]]);
            }

            // Envía múltiples mensajes en un solo lote
            $this->messaging->sendAll($messages);

            return response()->json(['message' => 'Notificaciones enviadas a múltiples usuarios.'], 200);
            Log::info("Notificaciónes enviadas con éxito a: " . $deviceTokens);

        } catch (\Throwable $e) {
            Log::error("Error al enviar notificaciones FCM a múltiples usuarios: " . $e->getMessage());
            return response()->json(['error' => 'Error al enviar notificaciones: ' . $e->getMessage()], 500);
        }
    }

    public function getContentAlert(int $value,Sensor $sensor)
    {
        $alert = [
            'title' => '',
            'body' => '',
            'type' => ''
        ];
        $ubication = $sensor->location->name;
        if ($value <= $sensor->alert_min_value) {
            $alert['title'] = "Nivel Bajo de Agua - $ubication";
            $alert['body'] = "El nivel está por debajo del $sensor->alert_min_value%, en  $sensor->name ";
            $alert['type'] = 'min';
        } elseif ($value >= $sensor->alert_max_value) {
            $alert['title'] = "Nivel Alto de agua - $ubication";
            $alert['body'] = "El nivel está por encima del $sensor->alert_max_value%, en $sensor->name ";
            $alert['type'] = 'max';
        } else if($value > 60 && $value < $sensor->alert_max_value){ 
            $alert['title'] = 'Nivel Normal de agua - '.$ubication;
            $alert['body'] = "El nivel está en un rango normal ($value%), en $sensor->name ";
            $alert['type'] = 'normal';
        
        }
        return $alert;

        }

    }

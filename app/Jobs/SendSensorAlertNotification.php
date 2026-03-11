<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Sensor;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\MessagingException;
use App\Models\Notification as NotificationModel;

class SendSensorAlertNotification implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    protected array $readingData;
    protected string $alertType; // 'above' | 'below'
    protected float $valuePorcent;

    /**
     * Create a new job instance.
     */
    public function __construct(array $readingData, string $alertType, float $valuePorcent)
    {
        $this->readingData = $readingData;
        $this->alertType = $alertType;
        $this->valuePorcent = $valuePorcent;
    }
    

    /**
     * Execute the job.
     */
    public function handle(Messaging $messaging): void
    {
        Log::info("[AlertJob] Procesando alerta. Sensor ID: {$this->readingData['sensor_id']} | Tipo: {$this->alertType} | Valor: {$this->valuePorcent}%");
        $sensor = Sensor::find($this->readingData['sensor_id']);
        $location = $sensor->location;
        $region = $location->region;

        $type = $this->readingData["value"] < $sensor->alert_min_value ? 'min' : ($this->readingData["value"] > $sensor->alert_max_value ? 'max' : "normal");


         if (!$location) {
            Log::warning("[AlertJob] Location no encontrada para Sensor ID {$sensor->id}.");
            return;
        }

        if (!$sensor) {
            Log::warning("[AlertJob] Sensor ID {$this->readingData['sensor_id']} no encontrado.");
            return;
        }

        // Obtener usuarios relacionados con la location del sensor
        $users = $region->users ?? collect();

        if ($users->isEmpty()) {
            Log::info("[AlertJob] No hay usuarios para la location del sensor ID {$sensor->id}.");
            return;
        }

        

        // Filtrar tokens FCM válidos
        $tokens = $users
            ->pluck('fcm_token')
            ->filter()
            ->values()
            ->toArray();

        if (empty($tokens)) {
            Log::info("[AlertJob] Ningún usuario con fcm_token para sensor ID {$sensor->id}.");
            return;
        }

        [$title, $body] = $this->buildMessage($sensor);

        $this->sendNotifications($messaging, $tokens, $title, $body, $sensor);
        
        NotificationModel::create([
            'sensor_id' => $sensor->id,
            'title' => $title,
            'body' => $body,
            'type'=> $type,
            ]);
    }

    /**
     * Construye el título y cuerpo del mensaje según el tipo de alerta.
     */
    private function buildMessage(Sensor $sensor): array
    {
        $locationName = $sensor->location->name ?? 'Ubicación desconocida';
        $sensorName   = $sensor->name ?? "Sensor #{$sensor->id}";
        $value        = round($this->valuePorcent, 1);

        if ($this->alertType === 'above') {
            $title = "💧  Nivel alto detectado 💧";
            $body  = "{$sensorName} en {$locationName} superó el límite máximo. Nivel actual: {$value}%";
        }
        if ($this->alertType === 'below') {
            $title = " ⚠️ Nivel bajo detectado ⚠️";
            $body  = "{$sensorName} en {$locationName} está por debajo del mínimo. Nivel actual: {$value}%";
        }

        if($this->alertType ==='flujo'){
            switch ($value) {
                case $value < 4:
                    $title = "💧 Flujo de agua bajo detectado 💧";
                    $body  = "{$sensorName} en {$locationName} tiene un flujo bajo  {$value} L/min";
                    break;
                case $value > 7:
                    $title = "💧💧💧 Flujo de agua alto detectado";
                    $body  = "{$sensorName} en {$locationName} tiene un flujo alto  {$value} L/min";
                    break;
                default:
                    $title = "💧 Flujo de agua normal 💧";
                    $body  = "{$sensorName} en {$locationName} tiene un flujo normal  {$value} L/min";
                    break;
            }
        }

        

        return [$title, $body];
    }

    /**
     * Envía la notificación a cada token usando kreait/laravel-firebase.
     * sendAll() hace una sola llamada HTTP para hasta 500 tokens — muy eficiente.
     */
    private function sendNotifications(Messaging $messaging, array $tokens, string $title, string $body, Sensor $sensor): void
    {
        $notification = Notification::create($title, $body);

        $data = [
            'sensor_id'   => (string) $sensor->id,
            'location_id' => (string) ($sensor->location_id ?? ''),
            'alert_type'  => $this->alertType,
            'value'       => (string) round($this->valuePorcent, 1),
        ];

        // Construir un mensaje por token
        $messages = array_map(
            fn(string $token) => CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData($data)
                ->withAndroidConfig([
                    'priority' => 'high',
                    'notification' => [
                        'sound'      => 'default',
                        'channel_id' => 'sensor_alerts',
                    ],
                ])
                ->withApnsConfig([
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ]),
            $tokens
        );

        try {
            // sendAll: 1 sola llamada HTTP para todos los tokens
            $report = $messaging->sendAll($messages);

            Log::info(
                "[AlertJob] FCM enviado. Sensor: {$sensor->id} | " .
                "Exitosos: {$report->successes()->count()} | " .
                "Fallidos: {$report->failures()->count()}"
            );

            // Limpiar tokens inválidos automáticamente
            if ($report->hasFailures()) {
                $this->handleFailedTokens($report, $tokens);
            }

        } catch (MessagingException $e) {
            Log::error("[AlertJob] Error FCM: " . $e->getMessage());
            throw $e; // relanza para que el job reintente (tries = 3)
        }
    }

    /**
     * Elimina de la BD los tokens que FCM reporta como inválidos
     * (usuario desinstalò la app, token expirado, etc.)
     */
    private function handleFailedTokens($report, array $tokens): void
    {
        foreach ($report->failures()->getItems() as $failure) {
            $failedToken = $tokens[$failure->messageIndex()] ?? null;

            if (!$failedToken) {
                continue;
            }

            if ($failure->error() instanceof NotFound) {
                \App\Models\User::where('fcm_token', $failedToken)
                    ->update(['fcm_token' => null]);

                Log::info("[AlertJob] Token FCM inválido eliminado: ...{$this->maskToken($failedToken)}");
            } else {
                Log::warning(
                    "[AlertJob] Fallo FCM en token ...{$this->maskToken($failedToken)}: " .
                    $failure->error()->getMessage()
                );
            }
        }
    }

    private function maskToken(string $token): string
    {
        return substr($token, -8);
    }

}

<?php

namespace App\Jobs;

use App\Jobs\SendSensorAlertNotification;
use App\Models\Reading;
use App\Models\Sensor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Notification as NotificationModel;

class ProcessMqttReading implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $readingData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $readingData)
    {
        $this->readingData = $readingData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {   
         $sensor = Sensor::find($this->readingData['sensor_id']);
         $lastReading = $sensor->latestReading;

         if (!$sensor) {
            Log::warning("[ProcessMqttReading] Sensor ID {$this->readingData['sensor_id']} no encontrado.");
            return;
        }
        try {
            if($lastReading != null){
                $this->checkThresholdsAndAlert($sensor);
                //TODO: validar que lastReading no se null, si es null guardar la lectura
                $tiempoDesdeUltimaLectura = now()->diffInMinutes($lastReading->created_at);
                if($lastReading->created_at->diffInMinutes(now()) < ($sensor->interval_reading)){
                    Log::info("[ProcessMqttReading] Intervalo de guardado de lecturas no alcanzado. Última lectura: {$lastReading->created_at} tiempo desde la ultima lectura guardada: ${tiempoDesdeUltimaLectura}");
                    return;
                }

                Reading::create($this->readingData);
                Log::info("[ProcessMqttReading] Lectura guardada. Sensor: {$this->readingData['sensor_id']} | Valor: {$this->readingData['value']}");
            }else{
                $this->checkThresholdsAndAlert($sensor);
                Reading::create($this->readingData);
            }
            

        } catch (\Exception $e) {
            Log::error('Error al guardar la lectura desde la cola: ' . $e->getMessage());
        }
    }

    /**
     * Evalúa si el valor está fuera del rango [min_value, max_value]
     * y despacha el job de notificación correspondiente.
     */
    private function checkThresholdsAndAlert(Sensor $sensor): void
    {

        $lastNotification = NotificationModel::where('sensor_id', $sensor->id)
                ->orderBy('created_at', 'desc')
                ->first();
        Log::info("[ProcessMqttReading] Tipo de sensor : {$sensor->sensorType->name} ");
        $lastFiveReadings = $sensor->readings()->latest()->take(5)->get();
       if($lastNotification != null && $lastFiveReadings->isNotEmpty() && $sensor->sensorType->name == 'flujo'){

        $lastFiveReadings = $lastFiveReadings->sortByDesc('created_at');
        $lastOneReading = $lastFiveReadings->first();
        $pastTimeNecessary = $lastOneReading->created_at < now()->subMinutes($sensor->alert_notification_interval * 60);
        Log::info("[ProcessMqttReading] Última lectura: {$lastOneReading->created_at} | Tiempo necesario para alerta: " . ($sensor->alert_notification_interval * 60) . " minutos | Tiempo desde última lectura: " . now()->diffInMinutes($lastOneReading->created_at) . " minutos");
        // ✅ Verifica si todos los valores son cero y si ha pasado el tiempo necesario para enviar una alerta
        $allZero = $lastFiveReadings->every(fn($reading) => $reading->value == 0) || $pastTimeNecessary;
        Log::info("[ProcessMqttReading] allZero : " . ($allZero == true ? 'true' : 'false'));
       }else{
        $allZero = true;
       }
        
        

        if($sensor->sensorType->name == 'flujo' && $allZero){
            
             $valuePorcent = (float) $this->readingData['value'];
             
            SendSensorAlertNotification::dispatch(
                    $this->readingData,
                    'flujo',
                    $valuePorcent
                )->onQueue('notifications');
        }

        if($sensor->sensorType->name == 'distancia'){
            $this->sendNotificationSensorDistancia($sensor, $lastNotification);
        }

        
    }

    public function sendNotificationSensorDistancia(Sensor $sensor, NotificationModel $lastNotification): void
    {
        // Calcular el porcentaje real (misma lógica del MqttSubscriber)
        $rawValue = (float) $this->readingData['value'];
        $valuePorcent = $rawValue;

        if ($valuePorcent === null) {
            // No se pudo calcular, no hay alerta
            return;
        }

        Log::info("[ProcessMqttReading] Porcentaje calculado: {$valuePorcent}% | Sensor: {$sensor->id}");

        // ---- Alerta: nivel por ENCIMA del máximo (porcentaje = 0 o negativo) ----
        if ($valuePorcent >= $sensor->alert_max_value ) {
            Log::warning("[ProcessMqttReading] ALERTA NIVEL ALTO. Sensor: {$sensor->id} | Porcentaje: {$valuePorcent}%");
             //TODO: validar que lastNotification no se null, si es null enviar notification
            if($lastNotification != null){
                $tiempoDesdeUltimaNotificacion =  now()->diffInMinutes(\Carbon\Carbon::parse($lastNotification->created_at));
                if($lastNotification->created_at->diffInMinutes(now()) < $sensor->alert_notification_interval * 60){
                    Log::info("[ProcessMqttReading] Alerta descartada por intervalo de notificación. Última notificación: {$lastNotification->created_at} tiempo que a pasado : ${tiempoDesdeUltimaNotificacion}");
                    return;
                }
            
                $tiempoDesdeUltimaNotificacion =  now()->diffInMinutes(\Carbon\Carbon::parse($lastNotification->created_at));
                if($lastNotification->created_at->diffInMinutes(now()) < $sensor->alert_notification_interval * 60){
                    Log::info("[ProcessMqttReading] Alerta descartada por intervalo de notificación. Última notificación: {$lastNotification->created_at} tiempo que a pasado : ${tiempoDesdeUltimaNotificacion}");
                    return;
                }
                Log::info("[ProcessMqttReading] Despachando alerta de nivel alto para Sensor ID: {$sensor->id} con valor: {$valuePorcent}%");
                SendSensorAlertNotification::dispatch(
                    $this->readingData,
                    'above',
                    $valuePorcent
                )->onQueue('notifications');
            }else{
                
                SendSensorAlertNotification::dispatch(
                    $this->readingData,
                    'above',
                    $valuePorcent
                )->onQueue('notifications');
            }
            return;
        }
        // ---- Alerta: nivel por DEBAJO del mínimo (porcentaje = 100 o positivo) ----

        if ($valuePorcent <= $sensor->alert_min_value) {
                Log::warning("[ProcessMqttReading] ALERTA NIVEL BAJO. Sensor: {$sensor->id} | Porcentaje: {$valuePorcent}%");
                //TODO: validar que lastNotification no se null, si es null enviar notification
            if($lastNotification != null){
                    $tiempoDesdeUltimaNotificacion =  now()->diffInMinutes(\Carbon\Carbon::parse($lastNotification->created_at));
                    if($lastNotification->created_at->diffInMinutes(now()) < $sensor->alert_notification_interval * 60){
                        Log::info("[ProcessMqttReading] Alerta descartada por intervalo de notificación. Última notificación: {$lastNotification->created_at} tiempo que a pasado: ${tiempoDesdeUltimaNotificacion}");
                        return;
                    }

                    SendSensorAlertNotification::dispatch(
                        $this->readingData,
                        'below',
                        $valuePorcent
                    )->onQueue('notifications');
            }else{
               
                SendSensorAlertNotification::dispatch(
                    $this->readingData,
                    'below',
                    $valuePorcent
                )->onQueue('notifications');
            }
             return;
        }
    }
}

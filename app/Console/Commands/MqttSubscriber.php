<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessMqttReading;
use App\Models\Sensor;


class MqttSubscriber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mqtt-subscriber';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Suscribe al topico de MQTT  para guardar las lecturas de los sensores';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Iniciando suscripción MQTT...');
        try{
        
        $server = '52.8.74.58';
        $port = 1883;
        $clientId = 'laravel-app';
        $topic = 'edificio/#';

        $mqtt = new MqttClient($server, $port, $clientId);
        Log::info("Connecting to broker at {$server}:{$port}...");
        $mqtt->connect(null,true,300);
        Log::info("Connected to broker. Subscribing to topic '{$topic}'...");

        $mqtt->subscribe($topic, function ($topic, $message) {
            // Aquí puedes procesar el mensaje recibido
            
            $data = json_decode($message, true);
            //Log::info("Mensaje recibido en el tópico {$topic}: ".$message);
            
            Log::info("Mensaje recibido en el tópico {$topic}: ID: {$data['sensor_id']} - Value: {$data['value']}");

            if (isset($data['value']) && isset($data['sensor_id']) && $data['tipo'] == 'distancia') {

                $sensor = Sensor::findOrFail($data['sensor_id']);
                                            
                $valueResult = (int) ($data['value'] - $sensor->min_value) / ($sensor->max_value - $sensor->min_value) * 100;
                $valuePorcentReal = 100 -$valueResult;

                $readingData = [
                'sensor_id' => $data['sensor_id'],
                'value' => $valuePorcentReal
                ];

                // Enviar el trabajo a la cola
                ProcessMqttReading::dispatch($readingData);

            }
            
            
        });

        $mqtt->loop(true);
        }catch(\Exception $e){
            Log::error('Error en la suscripción MQTT: ' . $e->getMessage());
        }
        
    }
}

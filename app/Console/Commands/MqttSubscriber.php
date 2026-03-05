<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessMqttReading;
use App\Models\Sensor;
use App\Models\Reading;
use PhpMqtt\Client\ConnectionSettings;


class MqttSubscriber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mqtt-subscriber';

    protected $readingData = null;

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
        
        /*$server = '157.245.176.211';
        $port = 1883;
        $clientId = 'laravel-app';
        $topic = 'edificio/#';
        $keepAlive = 60;
        $username = 'cheke';
        $password = '123456';*/

        $server   = env('MQTT_HOST');
        $port     = env('MQTT_PORT');
        $clientId = env('MQTT_CLIENT_ID');
        $username = env('MQTT_USER');
        $password = env('MQTT_PASSWORD');
        $topic    = env('MQTT_TOPIC');

        $settings = (new ConnectionSettings)
            ->setUsername($username)
            ->setPassword($password)
            ->setKeepAliveInterval(60);

        $mqtt = new MqttClient($server, $port, $clientId);
        Log::info("Connecting to broker at {$server}:{$port}...");
        $mqtt->connect($settings);
        Log::info("Connected to broker. Subscribing to topic '{$topic}'...");

        $mqtt->subscribe($topic, function ($topic, $message) {
            // Aquí puedes procesar el mensaje recibido
            
            $data = json_decode($message, true);
            //Log::info("Mensaje recibido en el tópico {$topic}: ".$message);

            Log::info("Mensaje recibido en el tópico {$topic}: ID: {$data['sensor_id']} - Value: {$data['value']}- Tipo: {$data['tipo']}");
            

            if (!empty($data['value']) && !empty($data['sensor_id']) && $data['tipo'] == 'distancia') {
                
                $sensor = Sensor::findOrFail($data['sensor_id']);
                 

                switch ($data['value']) {
                    case ($data['value'] + $sensor->min_value) >= $sensor->max_value:
                        $valuePorcentReal = 0;
                        break;
                    case $data['value'] >= $sensor->min_value && $data['value'] < $sensor->max_value:
                        // Normalizar el valor al rango 0-100
                        $valueResult = round((($sensor->max_value - ($data['value'] + $sensor->min_value )) / $sensor->max_value)   * 100);
                        $valuePorcentReal = $valueResult;
                        break;
                    default:
                        # code...
                        break;
                }
    
                $readingData = [
                'sensor_id' => $sensor->id,
                'value' => $data['value']
                ];
                
                //$lastReading = Reading::where('sensor_id', $sensor->id)->latest()->first(); 
                //$toleranciaMax = 20.0;
                //$toleranciaMin = 20.0;
                //$valorResultado = abs($lastReading->value - $valuePorcentReal);
                
                //if( $valorResultado <= $toleranciaMax || $valorResultado <= $toleranciaMin ){
                    ProcessMqttReading::dispatch($readingData);
                //}else{
                //    Log::info("Lectura descartada por estar fuera de la tolerancia con valor: {$valuePorcentReal}");
                //}
                
            }
   
            if (isset($data['value']) && !empty($data['sensor_id']) &&  $data['tipo'] == 'flujo') {
                $sensorFlujo = Sensor::findOrFail($data['sensor_id']);

                $readingData = [
                'sensor_id' => $sensorFlujo->id,
                'value' => (float) $data['value'],
                'created_at' => now(),
                'updated_at' => now(),
                ];

                Log::info("Despachando job para sensor ID: {$sensorFlujo->id} con valor: {$data['value']}");
                ProcessMqttReading::dispatch($readingData);

            }
            
            
        });

        $mqtt->loop(true);
        }catch(\Exception $e){
            Log::error('Error en la suscripción MQTT: ' . $e->getMessage());
        }
        
    }
}

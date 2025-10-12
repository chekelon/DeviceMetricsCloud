<?php

namespace App\Http\Controllers;

use App\Http\Resources\SensorResources;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Sensor;
use Carbon\Carbon;
use App\Models\User;
use App\Models\location;
use Illuminate\Support\Facades\Log;

class SensorController extends Controller
{
    

    public function store(Request $request)
    {   
        $validator = null;

        if($request->type_sensor == 'ultrasonico'){
            $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type_sensor'=>'required|string|exists:sensors_type,name',
            'location' => 'required|string|exists:locations,name',
            'almacenamiento' => 'required|string',
            'alert_max_value' => 'required|string',
            'alert_min_value' => 'required|string',
            'min_value' => 'required|string',
            'max_value' => 'required|string',
            'alert_notification_interval' => 'required|string', // Intervalo de notificación de alerta en hrs
            'interval_reading'=>'required|string', // Intervalo de lectura en minutos
            ]);
        }else{
            $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type_sensor'=>'required|string|exists:sensors_type,name',
            'location' => 'required|string|exists:locations,name',
            ]);
        }
        
        
        

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        
        $type_sensor = \App\Models\SensorType::where('name', $request->type_sensor)->first();
        $location = \App\Models\Location::where('name',$request->location)->first();

        if($request->type_sensor == 'ultrasonico'){
            $sensor = Sensor::create([
            'name' => $request->name,
            'type_sensor_id'=> $type_sensor->id,
            'location_id' => $location->id,
            'almacenamiento' => $request->almacenamiento,
            'min_value' => $request->min_value,
            'max_value' => $request->max_value,
            'alert_max_value' => $request->alert_max_value,
            'alert_min_value' => $request->alert_min_value,
            'alert_notification_interval' => $request->has('alert_notification_interval') ? $request->alert_notification_interval : 1,
            'interval_reading' => $request->has('interval_reading') ? $request->interval_reading : 60, // Intervalo de lectura en minutos
        ]);

        }else{
            $sensor = Sensor::create([
                'name' => $request->name,
                'type_sensor_id'=> $type_sensor->id,
                'location_id' => $location->id,
            ]);
        }

        

        return response()->json($sensor, 201);
    }

    public function show(Request $request, $id)
    {
        // Buscar el sensor por ID
        $sensor = Sensor::with(['readings', 'notifications'])->find($id);
        
        if (!$sensor) {
            return response()->json(['message' => 'Sensor not found'], 404);
        }
    
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date', // end_date debe ser igual o posterior a start_date
        ]);

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // 2. Cargar las lecturas condicionalmente
        if ($startDate && $endDate) {
            // Si ambas fechas están presentes, filtra la relación 'readings'
            $sensor->load([
                'readings' => function ($query) use ($startDate, $endDate) {
                    $query->whereDate('created_at', '>=', $startDate)// mas antigua
                          ->whereDate('created_at', '<=', $endDate)// mas reciente
                          ->orderBy('created_at', 'asc'); // Opcional: ordenar las lecturas
                }
            ]);
        }else{
            $sensor->load([
                'readings' => function ($query)  {
                    $query->whereDate('created_at', '>=', Carbon::now()->subDays(8)->toDateString())
                          ->whereDate('created_at', '<=', Carbon::now()->toDateString())
                          ->orderBy('created_at', 'asc'); // Opcional: ordenar las lecturas
                }
            ]);
        }


        if (!$sensor) {
            return response()->json(['message' => 'Sensor not found'], 404);
        }
        $latestReading = $sensor->latestReading()->first();
        if (!$latestReading) {
            return response()->json(['message' => 'No readings found for this sensor'], 404);
        }
    
    
        return response()->json(new SensorResources($sensor), 200);
       
    }

    public function searchByName(Request $request)
    {

        $search = $request->search;

        $sensors = Sensor::where('name','=',$search)
            ->orWhereHas('location', function ($query) use ($search) {
                $query->where('name','=',$search );
            })
            ->with('location')
            ->get();

        if ($sensors->isEmpty()) {
            return response()->json(['message' => 'No sensors found'], 404);
        }

        return response()->json($sensors, 200);
    }

    public function history(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            //'type_history' => 'required|in:daily,weekly,monthly',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        // Validar el ID del sensor
        $sensor = Sensor::with('readings','notifications')->find($id);

        if (!$sensor) {
            return response()->json(['message' => 'Sensor not found'], 404);
        }
        $date_start = $request->get('start_date', Carbon::now()->subDays(8)->toDateString());
        $date_end = $request->get('end_date', Carbon::now()->toDateString());
       

        $sensor->load([
            'readings' => function ($query) use ($date_start, $date_end) {
                $query->whereDate('created_at', '>=', $date_start)
                      ->whereDate('created_at', '<=', $date_end)
                      ->orderBy('created_at', 'asc');
            }
        ]);

        return response()->json(new SensorResources($sensor), 200);
    }

    public function update(Request $request,$id)
    {
        // 1. Definir reglas de validación
        // Para el 'name', usamos Rule::unique para asegurarnos de que el nombre sea único,
        // pero ignoramos el nombre del sensor actual que estamos actualizando.
        $sensor = Sensor::find($id);

        if (!$sensor) {
            return response()->json(['message' => 'Sensor not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required','string','max:255',
            'user' => 'required|string|exists:users,name',
            'location' => 'required|string|exists:locations,name',
            'almacenamiento' => 'required|string',
            'alert_max_value' => 'required|string',
            'alert_min_value' => 'required|string',
            'min_value' => 'required|string',
            'max_value' => 'required|string',
            'alert_notification_interval' => 'required|string', // Intervalo de notificación de alerta en hrs
            'interval_reading' => 'required|string', // Intervalo de lectura en minutos
        ]);

        // 2. Manejar fallos de validación
        if ($validator->fails()) {
            // Devolver una respuesta JSON con los errores de validación y un código de estado 422 (Unprocessable Entity)
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 3. Obtener los IDs de user y location a partir de sus nombres
        // Esto asume que tienes un campo 'name' en tus tablas 'users' y 'locations'
        $user = User::where('name', $request->user)->first();
        $location = Location::where('name', $request->location)->first();

        // Verificar si el usuario o la ubicación existen
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }
        if (!$location) {
            return response()->json(['message' => 'Location not found.'], 404);
        }

        // 4. Actualizar los atributos del sensor
        // Asegúrate de que los nombres de los campos en la base de datos coincidan
        // con los nombres de los campos en el request.
        $sensor->name = $request->name;
        $sensor->user_id = $user->id; // Asigna el ID del usuario
        $sensor->location_id = $location->id; // Asigna el ID de la ubicación
        $sensor->almacenamiento = $request->almacenamiento;
        $sensor->alert_max_value = $request->alert_max_value;
        $sensor->alert_min_value = $request->alert_min_value;
        $sensor->min_value = $request->min_value;
        $sensor->max_value = $request->max_value;
        $sensor->alert_notification_interval = $request->alert_notification_interval;
        $sensor->interval_reading = $request->interval_reading;

        // 5. Guardar los cambios en la base de datos
        $sensor->save();

        // 6. Devolver una respuesta JSON de éxito con el sensor actualizado
        return response()->json($sensor, 200);
    }

    public function showById($id)
    {
        // Buscar el sensor por ID
        $sensor = Sensor::find($id);
        
        if (!$sensor) {
            return response()->json(['message' => 'Sensor not found'], 404);
        }

        return response()->json(new SensorResources($sensor), 200);

    }
}

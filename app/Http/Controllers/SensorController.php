<?php

namespace App\Http\Controllers;

use App\Http\Resources\SensorResources;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Sensor;
use Carbon\Carbon;

class SensorController extends Controller
{
    

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location_id' => 'required|integer|exists:locations,id',
            'almacenamiento' => 'required|string|min:1|max:100',
            'alert_max_value' => 'required|numeric',
            'alert_min_value' => 'required|numeric',
            'min_value' => 'required|numeric',
            'max_value' => 'required|numeric',
            'alert_notification_interval' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $sensor = Sensor::create([
            'name' => $request->name,
            'location_id' => $request->location_id,
            'almacenamiento' => $request->almacenamiento,
            'min_value' => $request->min_value,
            'max_value' => $request->max_value,
            'alert_max_value' => $request->alert_max_value,
            'alert_min_value' => $request->alert_min_value,
            'alert_notification_interval' => $request->has('alert_notification_interval') ? $request->alert_notification_interval : 1,
        ]);

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
}

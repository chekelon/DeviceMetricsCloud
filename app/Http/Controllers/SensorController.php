<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Sensor;

class SensorController extends Controller
{
    

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location_id' => 'required|integer|exists:locations,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $sensor = Sensor::create([
            'name' => $request->name,
            'location_id' => $request->location_id,
        ]);

        return response()->json($sensor, 201);
    }

    public function show($id)
    {

        $sensor = Sensor::find($id);
        if (!$sensor) {
            return response()->json(['message' => 'Sensor not found'], 404);
        }
        $latestReading = $sensor->latestReading()->first();
        if (!$latestReading) {
            return response()->json(['message' => 'No readings found for this sensor'], 404);
        }
    
        $data = [
            'location' => $sensor->location->name,
            'sensor' => $sensor->name,
            'latest_reading' => $latestReading->value
        ];
        return response()->json($data);
       
    }
}

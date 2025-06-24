<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Reading;

class ReadingController extends Controller
{

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
        $reading = \App\Models\Reading::create([
            'sensor_id' => $request->sensor_id,
            'value' => $valuePorcentReal,
            
        ]);

        return response()->json($reading, 201);
    }


}

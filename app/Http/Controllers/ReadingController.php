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

        $reading = \App\Models\Reading::create([
            'sensor_id' => $request->sensor_id,
            'value' => $request->value,
        ]);

        return response()->json($reading, 201);
    }


}

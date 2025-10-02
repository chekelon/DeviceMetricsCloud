<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\TypeSensorResource;

class TypeSensorController extends Controller
{
    public function index()
    {
        $typesensors = \App\Models\SensorType::all();
        return response()->json([
            'typesensors' =>  TypeSensorResource::collection($typesensors)]
        );
    }
}

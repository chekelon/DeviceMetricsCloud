<?php

namespace App\Http\Controllers;

use App\Http\Resources\LocationResources;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Location;



class LocationController extends Controller
{
    
    public function index()
    {
        $locations = LocationResources::collection(Location::with('sensors')->get());
        
        return response()->json([
            'locations' => $locations,]);
    }

    public function show($id)
    {
        $location = Location::with('sensors')->find($id);
        if (!$location) {
            return response()->json(['message' => 'Location not found'], 404);
        }
        return response()->json(new LocationResources($location));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'region' => 'required|string|exists:regions,name',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $region = \App\Models\Region::where('name', $request->region)->first();

        $location = Location::create([
            'name' => $request->name,
            'region_id' => $region->id,
        ]);

        return response()->json($location, 201);
    }
}

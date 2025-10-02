<?php

namespace App\Http\Controllers;

use App\Http\Resources\RegionResources;
use App\Models\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    

    public function index()
    {
        $regions = Region::all();

        return response()->json([
            "regiones"=>$regions]);
    }
    public function show($id)
    {
        $region = Region::with('locations.sensors')->findOrFail($id);

        return response()->json(new RegionResources($region),200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // Agrega aquí otros campos según tu modelo Region
        ]);

        // Verifica si ya existe una región con el mismo nombre
        if (Region::where('name', $validated['name'])->exists()) {
            return response()->json([
                'message' => 'La región ya existe.'
            ], 409); // 409 Conflict
        }

        $region = Region::create($validated);

        return response()->json(new RegionResources($region), 201);
    }
    
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\RegionResources;
use App\Models\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    

    public function index()
    {
        $regions = Region::with('locations')->get();

        return response()->json($regions);
    }
    public function show($id)
    {
        $region = Region::with('locations.sensors')->findOrFail($id);

        return response()->json(new RegionResources($region),200);
    }
}

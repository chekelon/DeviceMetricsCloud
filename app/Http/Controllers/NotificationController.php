<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use App\Models\Sensor;

class NotificationController extends Controller
{
    


    public function show(Request $request, $id)
    {


        $sensor = Sensor::findOrFail($id);
        $notifications = $sensor->notifications()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(NotificationResource::collection($notifications), 200);

    }
}

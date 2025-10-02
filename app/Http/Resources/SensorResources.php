<?php

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;
use App\Models\SensorType;

class SensorResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $leatestReading = $this->latestReading ? $this->latestReading : null;
        $typeSensor = SensorType::find($this->type_sensor_id);
        //now()->toDateString()
        // Calcular el volumen actual si el sensor es de tipo 'flujo'
        $readingsToday = $this->readings()->whereDate('created_at', now()->toDateString())->get();
        $currentValumen = $readingsToday->sum('value');


        return [
            'id' => $this->id,
            'name' => $this->name,
            'tipo_sensor'=> $typeSensor->name,
            'almacenamiento' => $this->almacenamiento,
            'alert_max_value'=> $this->alert_max_value, 
            'alert_min_value'=> $this->alert_min_value,
            'min_value'=> $this->min_value,
            'max_value'=> $this->max_value,
            'interval_notification'=> $this->alert_notification_interval,
            'interval_reading'=> $this->interval_reading,
            'latest_reading' => $leatestReading ? $leatestReading->value : null,
            'current_volumen' => $typeSensor->name == 'flujo' ? round((float)$currentValumen, 2) : 0.0,
            'created_at' => $leatestReading != null ? $leatestReading->created_at->format('d-m-Y H:i') :null,
            'notifications'=> NotificationResource::collection($this->whenLoaded('notifications')),
            'readings' => ReadingResources::collection($this->whenLoaded('readings')),
            'location' => $this->location->name,
        ];
    }
}

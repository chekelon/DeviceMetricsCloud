<?php

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'almacenamiento' => $this->almacenamiento,
            'alert_max_value'=> $this->alert_max_value, 
            'alert_min_value'=> $this->alert_min_value,
            'latest_reading' => $leatestReading ? $leatestReading->value : null,
            'created_at' => $leatestReading != null ? $leatestReading->created_at->format('d-m-Y H:i') :null,
            'notifications'=> NotificationResource::collection($this->whenLoaded('notifications')),
            'readings' => ReadingResources::collection($this->whenLoaded('readings')),
            
        ];
    }
}

<?php

namespace App\Http\Resources;

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
            'latest_reading' => $leatestReading ? $leatestReading->value : null,
            'created_at' => $leatestReading != null ? $leatestReading->created_at->format('d-m-Y H:i') :null,
            'readings' => ReadingResources::collection($this->whenLoaded('readings')),
            
        ];
    }
}

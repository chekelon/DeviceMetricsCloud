<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Location;
use App\Models\Sensor;

class DeviceDashboard extends Component
{

    public $locations = [];
    public $readingsSensors = []; 

    public function mount()
    {
         $this->locations = Location::with('sensors.sensorType')->get()->toArray();
    
    }

    public function updateSensorData(Location $location,)
    {
        $data = $location->sensors->mapWithKeys(function ($sensor) {
            return [
            $sensor->id => [
                'sensor_id' => $sensor->id,
                'tipo' => $sensor->sensorType->name,
                'readings' => $sensor->readings()
                ->whereDate('created_at',today())
                ->latest()
                ->take(30)
                ->get()
                ->toArray()
            ]
            ];
        })->toArray();

        $this->readingsSensors = array_replace($this->readingsSensors, $data);

    }


    public function render()
    {
        return view('livewire.device-dashboard');
    }
}

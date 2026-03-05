<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorType extends Model
{
    protected $table = 'sensors_type'; 

    protected $fillable = [
        'name',
        'description'
    ];

    public function sensors()
    {
        return $this->hasMany(Sensor::class, 'sensor_type_id');
     }
}

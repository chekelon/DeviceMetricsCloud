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
}

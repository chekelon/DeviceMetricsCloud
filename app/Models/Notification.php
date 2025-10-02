<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    
    protected $fillable = [
        'sensor_id',
        'title',
        'body',
        'type',
    ];

    /**
     * Get the sensor that owns the notification.
     */
    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }


}

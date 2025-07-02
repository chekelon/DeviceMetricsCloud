<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sensor extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 
        'user_id',
        'location_id',
        'almacenamiento',
        'alert_max_value', 
        'alert_min_value',
        'min_value',
        'max_value',
        ];

    /**
     * Get the location that owns the sensor.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the readings for the sensor.
     */
    public function readings()
    {
        return $this->hasMany(Reading::class);
    }

    public function latestReading()
    {
        return $this->hasOne(Reading::class)->latestOfMany();
    }

    /**
     * Get the notifications for the sensor.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class)->orderByDesc('created_at')->limit(4);
    }

    
}

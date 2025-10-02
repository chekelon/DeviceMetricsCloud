<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reading extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['sensor_id', 'value', 'timestamp'];

    /**
     * Get the sensor that owns the reading.
     */
    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    /**
     * Get the formatted timestamp for the reading.
     *
     * @return string
     */
    public function getFormattedTimestampAttribute()
    {
        return $this->timestamp->format('Y-m-d H:i:s');
    }
}

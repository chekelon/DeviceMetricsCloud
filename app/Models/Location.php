<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'region_id'];

    /**
     * Get the region that owns the location.
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the sensors for the location.
     */
    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }
}

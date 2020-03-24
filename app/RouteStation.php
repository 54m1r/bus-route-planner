<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RouteStation extends Model
{
    protected $fillable = ['route_id', 'station_id'];

    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    protected $fillable = ['name', 'position', 'rotation', 'hash'];

    public function routes()
    {
        return $this->hasMany(RouteStation::class, 'station_id');
    }

    protected $casts = [
        'position' => 'array'
    ];
}

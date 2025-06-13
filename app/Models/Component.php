<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    // use HasFactory; // Add this
    protected $primaryKey = 'ComponentID';

    protected $fillable = [
        'name',
        'AreaID',
        'desc',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'AreaID', 'AreaID');
    }

    public function logs()
    {
        return $this->hasMany(LogData::class, 'ComponentID', 'ComponentID');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogData extends Model
{
    protected $primaryKey = 'LogID';

    protected $fillable = [
        'ComponentID',
        'OperatorID',
        'LogValue',
        'LogTimestamp',
        'Notes',
    ];

    protected $casts = [
        'LogTimestamp' => 'datetime',
    ];

    public function component()
    {
        return $this->belongsTo(Component::class, 'ComponentID', 'ComponentID');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'OperatorID', 'id');
    }
}
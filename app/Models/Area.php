<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $primaryKey = 'AreaID';

    protected $fillable = [
        'name',
        'DepartmentID',
        'desc',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'DepartmentID', 'DepartmentID');
    }

    public function components()
    {
        return $this->hasMany(Component::class, 'AreaID', 'AreaID');
    }
}
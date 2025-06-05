<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $primaryKey = 'DepartmentID';

    protected $fillable = [
        'name',
        'desc',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'DepartmentID', 'DepartmentID');
    }

    public function areas()
    {
        return $this->hasMany(Area::class, 'DepartmentID', 'DepartmentID');
    }
}
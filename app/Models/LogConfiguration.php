<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogConfiguration extends Model
{
    protected $table = 'log_configuration';
    protected $primaryKey = 'id';
    protected $fillable = [
        'DepartmentID',
        'AreaID',
        'frequency_type',
        'schedule',
        'created_at',
        'updated_at',
    ];

    // Relasi dengan Department
    public function department()
    {
        return $this->belongsTo(Department::class, 'DepartmentID', 'DepartmentID');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'AreaID', 'AreaID');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'DepartmentID',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => 'string',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'DepartmentID', 'DepartmentID');
    }

    public function logs()
    {
        return $this->hasMany(LogData::class, 'OperatorID', 'id');
    }

    public function initials()
    {
        $name = trim($this->name);
        if (empty($name)) {
            return '??';
        }
        $parts = preg_split('/[\s_]+/', $name);
        if (count($parts) === 1) {
            return strtoupper(substr($name, 0, 2));
        }
        $initials = '';
        for ($i = 0; $i < min(2, count($parts)); $i++) {
            $initials .= strtoupper(substr($parts[$i], 0, 1));
        }
        return $initials;
    }
}
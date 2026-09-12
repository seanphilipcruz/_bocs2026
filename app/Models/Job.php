<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'level',
        'is_active',
    ];

    public function Employee()
    {
        return $this->hasMany(Employee::class);
    }

    public function Logs()
    {
        return $this->hasMany(Log::class);
    }

    public function Description()
    {
        return $this->hasMany(Description::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    // contains employee logs creation of accounts and so forth.
    // action taken
    protected $fillable = [
        'employee_id',
        'job_id',
        'action',
    ];

    public function Employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function Job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgencyLog extends Model
{
    protected $fillable = [
        'agency_id',
        'action',
        'employee_id',
    ];

    public function Agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function Employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}

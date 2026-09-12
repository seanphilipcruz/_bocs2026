<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvertiserLog extends Model
{
    protected $fillable = [
        'advertiser_id',
        'action',
        'employee_id',
    ];

    public function Advertiser()
    {
        return $this->belongsTo(Advertiser::class, 'advertiser_id');
    }

    public function Employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}

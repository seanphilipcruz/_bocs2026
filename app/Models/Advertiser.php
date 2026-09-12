<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Advertiser extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'is_active',
    ];

    public function Contract()
    {
        return $this->hasMany(Contract::class, 'advertiser_id');
    }

    public function Logs()
    {
        return $this->hasMany(AdvertiserLog::class, 'advertiser_id');
    }

    public function Sale()
    {
        return $this->hasMany(Sale::class, 'advertiser_id');
    }
}

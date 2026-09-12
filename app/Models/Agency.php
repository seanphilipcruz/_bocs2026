<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agency extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'contact_number',
        'address',
        'is_accredited',
        'is_active',
    ];

    public function Contract()
    {
        return $this->hasMany(Contract::class);
    }

    public function Logs()
    {
        return $this->hasMany(AgencyLog::class);
    }

    public function Sale()
    {
        return $this->hasMany(Sale::class);
    }
}

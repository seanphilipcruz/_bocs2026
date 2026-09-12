<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractLog extends Model
{
    protected $fillable = [
        'contract_id',
        'action',
        'employee_id',
    ];

    public function Contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function Employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

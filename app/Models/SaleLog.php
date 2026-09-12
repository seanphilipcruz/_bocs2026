<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleLog extends Model
{
    protected $fillable = [
        'sales_id',
        'action',
        'employee_id',
    ];

    public function Sale()
    {
        return $this->belongsTo(Sale::class, 'sales_id');
    }

    public function Employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}

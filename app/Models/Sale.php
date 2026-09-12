<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contract_id',
        'employee_id',
        'agency_id',
        'advertiser_id',
        'station',
        'month',
        'year',
        'type',
        'transaction',
        'amount',
        'gross_amount',
        'invoice_amount',
        'invoice_no',
        'invoice_date',
    ];

    public function Employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function Contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function Agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function Advertiser()
    {
        return $this->belongsTo(Advertiser::class, 'advertiser_id');
    }

    public function Revision()
    {
        return $this->hasMany(SaleRevision::class, 'sales_id');
    }

    public function Logs()
    {
        return $this->hasMany(SaleLog::class, 'sales_id');
    }
}

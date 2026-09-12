<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleRevision extends Model
{
    protected $fillable = [
        'sales_id',
        'contract_id',
        'station',
        'month',
        'year',
        'type',
        'transaction',
        'amount',
        'gross_amount',
        'invoice_no',
        'version',
        'note',
    ];

    public function Sale()
    {
        return $this->belongsTo(Sale::class, 'sales_id');
    }

    public function Contract()
    {
        return $this->belongsTo(Contract::class);
    }
}

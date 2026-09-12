<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Description extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'job_id',
        'title',
        'description',
        'icon',
    ];

    public function Job()
    {
        return $this->belongsTo(Job::class);
    }
}

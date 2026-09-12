<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Employee extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'birthday',
        'nickname',
        'color',
        'email',
        'password',
        'job_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function Job()
    {
        return $this->belongsTo(Job::class);
    }

    public function ContractLogs()
    {
        return $this->hasMany(ContractLog::class, 'employee_id');
    }

    public function Logs()
    {
        return $this->hasMany(Log::class, 'employee_id');
    }

    public function Contract()
    {
        return $this->hasMany(Contract::class, 'employee_id');
    }

    public function Sale()
    {
        return $this->hasMany(Sale::class, 'employee_id');
    }
}

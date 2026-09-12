<?php

namespace App\Http\Traits;

use App\Models\Employee;
use Auth;
use Illuminate\Support\Facades\Hash;

trait SystemDefaultsTrait
{
    public function authenticatedUser($type = '')
    {
        if ($type === 'id') {
            return Auth::id();
        }

        if ($type === 'level') {
            return Auth::user()->Job->level;
        }

        if ($type === 'guard') {
            return Auth::guard();
        }

        return Employee::with('Job')->findOrFail(Auth::id());
    }

    public function password()
    {
        return Hash::make('MonsterRXBOCS');
    }
}

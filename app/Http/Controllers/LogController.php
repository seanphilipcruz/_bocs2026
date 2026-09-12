<?php

namespace App\Http\Controllers;

use App\Http\Traits\LogTrait;
use App\Http\Traits\ProcessorTrait;
use App\Http\Traits\SystemDefaultsTrait;
use App\Models\AdvertiserLog;
use App\Models\AgencyLog;
use App\Models\ContractLog;
use App\Models\Log;
use App\Models\SaleLog;
use Auth;
use Illuminate\Http\Request;

class LogController extends Controller
{
    use LogTrait;
    use ProcessorTrait;
    use SystemDefaultsTrait;

    public function index(Request $request)
    {
        $executive = Auth::id();
        $user_level = Auth::user()->Job->level;
        $logs = Log::with('Employee.Job')->latest()->get();

        $query = ! $request['logs'] ? 'employees' : $request['logs'];

        $this->Log(
            'Accessed Logs: ['.$query.']',
            $this->authenticatedUser('id')
        );

        if (preg_match('/contracts/', $query) === 1) {
            $logs = ContractLog::with('Contract', 'Employee.Job')->latest()->get();

            foreach ($logs as $employee) {
                $employee->name = $employee->Employee->first_name.' '.$employee->Employee->last_name;
                $employee->date_created = $this->formatDate($employee->created_at, 'Y, F d g:i:s a');
            }

            return response()->json([
                'contracts' => $logs,
            ]);
        }

        if (preg_match('/sales/', $query) === 1) {
            $logs = SaleLog::with('Sale.Contract', 'Employee.Job')->latest()->get();

            if ($user_level === '2') {
                $logs = SaleLog::with('Sale.Contract', 'Employee.Job')
                    ->where('employee_id', '=', $executive)
                    ->get();
            }

            foreach ($logs as $employee) {
                $employee->name = $employee->Employee->first_name.' '.$employee->Employee->last_name;
                $employee->date_created = $this->formatDate($employee->created_at, 'Y, F d g:i:s a');
            }

            return response()->json([
                'sales' => $logs,
            ]);
        }

        if (preg_match('/advertisers/', $query) === 1) {
            $logs = AdvertiserLog::with('Advertiser', 'Employee.Job')->latest()->get();

            foreach ($logs as $employee) {
                $employee->name = $employee->Employee->first_name.' '.$employee->Employee->last_name;
                $employee->date_created = $this->formatDate($employee->created_at, 'Y, F d g:i:s a');
            }

            return response()->json([
                'advertisers' => $logs,
            ]);
        }

        if (preg_match('/agencies/', $query) === 1) {
            $logs = AgencyLog::with('Agency', 'Employee.Job')->latest()->get();

            foreach ($logs as $employee) {
                $employee->name = $employee->Employee->first_name.' '.$employee->Employee->last_name;
                $employee->date_created = $this->formatDate($employee->created_at, 'Y, F d g:i:s a');
            }

            return response()->json([
                'agencies' => $logs,
            ]);
        }

        foreach ($logs as $employee) {
            $employee->name = $employee->Employee->first_name.' '.$employee->Employee->last_name;
            $employee->date_created = $this->formatDate($employee->created_at, 'Y, F d g:i:s a');
        }

        return response()->json([
            'logs' => $logs,
        ]);
    }
}

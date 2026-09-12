<?php

namespace App\Http\Traits;

use App\Models\AdvertiserLog;
use App\Models\AgencyLog;
use App\Models\ContractLog;
use App\Models\Log;
use App\Models\SaleLog;
use Illuminate\Support\Facades\Auth;

trait LogTrait
{
    public function Log($action, $employee_id, $type = null, $id = null): bool
    {
        $job_id = Auth::user()->Job->id;

        if ($type === 'advertisers') {
            if (! $id) {
                return false;
            }

            $log = new AdvertiserLog([
                'advertiser_id' => $id,
                'action' => $action,
                'employee_id' => $employee_id,
            ]);

            $log->save();

            return true;
        }

        if ($type === 'agencies') {
            if (! $id) {
                return false;
            }

            $log = new AgencyLog([
                'agency_id' => $id,
                'action' => $action,
                'employee_id' => $employee_id,
            ]);

            $log->save();

            return true;
        }

        if ($type === 'contracts') {
            if (! $id) {
                return false;
            }

            $log = new ContractLog([
                'contract_id' => $id,
                'action' => $action,
                'employee_id' => $employee_id,
            ]);

            $log->save();

            return true;
        }

        if ($type === 'sales') {
            if (! $id) {
                return false;
            }

            $log = new SaleLog([
                'sales_id' => $id,
                'action' => $action,
                'employee_id' => $employee_id,
            ]);

            $log->save();

            return true;
        }

        $log = new Log([
            'action' => $action,
            'employee_id' => $employee_id,
            'job_id' => $job_id,
        ]);

        $log->save();

        return true;
    }
}

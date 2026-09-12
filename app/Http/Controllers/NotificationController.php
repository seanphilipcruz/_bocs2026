<?php

namespace App\Http\Controllers;

use App\Http\Traits\ProcessorTrait;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ProcessorTrait;

    public function index(Request $request)
    {
        $executive = Auth::id();
        $user_level = Auth::user()->Job->level;
        $contract_type = $request['contract_type'] ? $request['contract_type'] : false;

        $latest_contract = Contract::has('Sale')
            ->with('Agency', 'Advertiser', 'Employee')
            ->latest()
            ->first();

        $filter = $request['contract_sales'] ? $request['contract_sales'] : false;
        $latest_year = date('Y', strtotime($latest_contract->created_at));

        $year_data = [];

        $contracts = Contract::has('Sale')
            ->with('Agency', 'Advertiser', 'Employee')
            ->where('is_active', '=', '1')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($contracts as $contract) {
            $contract->year = date('Y', strtotime($contract->created_at));
        }

        $years = $contracts->groupBy('year');

        foreach ($years as $key => $year) {
            $year_data[] = $key;
        }

        $year = $request['year'] == '' ? $latest_year : $request['year'];

        if ($user_level === '2') {
            $contracts = $this->getSalesByExecutives($executive, $year, $contract_type);

            return response()->json([
                'notifications' => $this->processContractSale($contracts, $filter, $user_level),
                'years' => $year_data,
            ]);
        }

        $contracts = $this->getSales($year, $contract_type);

        return response()->json([
            'notifications' => $this->processContractSale($contracts, $filter, $user_level),
            'years' => $year_data,
        ]);
    }
}

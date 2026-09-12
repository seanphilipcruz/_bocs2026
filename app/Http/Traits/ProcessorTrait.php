<?php

namespace App\Http\Traits;

use App\Models\Contract;
use App\Models\Sale;
use Auth;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

trait ProcessorTrait
{
    public function hasDecimal($number)
    {
        return preg_match('/^\d+\.\d+$/', $number);
    }

    public function formatDate($date, string $format)
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $date)->format($format);
    }

    public function hexToRGB($hex, $alpha = false)
    {
        $hex = str_replace('#', '', $hex);
        $length = strlen($hex);

        $rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
        $rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
        $rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));

        if ($alpha) {
            $rgb['a'] = $alpha;
        }

        return $rgb;
    }

    public function convertNumberToDate($date, $year = false)
    {
        if ($year) {
            $result = DateTime::createFromFormat('!Y', $date)->format('Y');
        } else {
            $result = DateTime::createFromFormat('!m', $date)->format('F');
        }

        return $result;
    }

    public function translateSaleType($type)
    {
        if ($type == 'airtime' || $type == 'totalamount') {
            $type = 'Air Time';
        }

        if ($type == 'top10') {
            $type = 'Top 10 Sponsorship';
        }

        if ($type == 'live') {
            $type = 'Live Guesting/Interview';
        }

        if ($type == 'DJDisc') {
            $type = 'DJ Discussion';
        }

        if ($type == 'Spots' || $type == 'spots') {
            $type = 'Spots';
        }

        if ($type == 'totalprod') {
            $type = 'Production';
        }

        return $type;
    }

    public function getStations($station)
    {
        // getting the radio stations by breaking down the data from the string
        $manila = preg_match('/DWRX Manila;/', $station);
        $another_manila_alias = preg_match('/DWRX 93.1 Manila;/', $station);
        $cebu = preg_match('/DYBT Cebu;/', $station);
        $another_cebu_alias = preg_match('/DYBT 105.9 Cebu;/', $station);
        $davao = preg_match('/DXBT Davao;/', $station);
        $another_davao_alias = preg_match('/DXBT 99.5 Davao;/', $station);

        $stations = [];

        if ($manila === 1 || $another_manila_alias === 1) {
            array_push($stations, 'DWRX Manila;');
        }

        if ($cebu === 1 || $another_cebu_alias) {
            array_push($stations, 'DYBT Cebu;');
        }

        if ($davao === 1 || $another_davao_alias) {
            array_push($stations, 'DXBT Davao;');
        }

        return $stations;
    }

    public function getExecutiveSalesReport($request, $type)
    {
        $executive_id = Auth::id();
        $agency_id = $request['agency_id'];
        $advertiser_id = $request['advertiser_id'];
        $station = $request['station'];
        $quarter = $request['quarter'];
        $month = $request['month'];
        $year = $request['year'];

        // Annual SR
        if ($type === 'yearly') {
            return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                ->latest()
                ->where('employee_id', '=', $executive_id)
                ->where('year', '=', $year)
                ->orderByDesc('month')
                ->get();
        }

        // Monthly SR
        if ($type === 'monthly') {
            return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                ->latest()
                ->where('employee_id', '=', $executive_id)
                ->where('month', '=', $month)
                ->where('year', '=', $year)
                ->get();
        }

        if ($type === 'executive') {
            return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                ->latest()
                ->where('employee_id', '=', $executive_id)
                ->where('year', '=', $year)
                ->get();
        }

        if ($type === 'station') {
            return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                ->latest()
                ->where('employee_id', '=', $executive_id)
                ->where('station', '=', $station)
                ->where('year', '=', $year)
                ->get();
        }

        if ($type === 'direct') {
            return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                ->latest()
                ->where('employee_id', '=', $executive_id)
                ->where('agency_id', '=', 1)
                ->where('year', '=', $year)
                ->get();
        }

        if ($type === 'agency') {
            return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                ->latest()
                ->where('employee_id', '=', $executive_id)
                ->where('agency_id', '=', $agency_id)
                ->where('year', '=', $year)
                ->get();
        }

        if ($type === 'advertiser') {
            return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                ->latest()
                ->where('employee_id', '=', $executive_id)
                ->where('advertiser_id', '=', $advertiser_id)
                ->where('year', '=', $year)
                ->get();
        }

        if ($type === 'quarterly') {
            return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                ->where('employee_id', '=', $executive_id)
                ->where('year', '=', $year)
                ->whereIn('month', $this->getQuarterMonths($quarter))
                ->orderBy('month')
                ->get();
        }
    }

    public function getSalesReport($request, $type, $all_time = false)
    {
        $employee_id = $request['employee_id'];
        $agency_id = $request['agency_id'];
        $advertiser_id = $request['advertiser_id'];
        $station = $request['station'];
        $quarter = $request['quarter'];
        $month = $request['month'];
        $year = $request['year'];

        // Annual SR
        if ($type === 'yearly') {
            if (isset($station)) {
                return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                    ->latest()
                    ->where('station', '=', $station)
                    ->where('year', '=', $year)
                    ->orderByDesc('month')
                    ->get();
            } else {
                return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                    ->latest()
                    ->where('year', '=', $year)
                    ->orderByDesc('month')
                    ->get();
            }
        }

        // Monthly SR
        if ($type === 'monthly') {
            return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                ->latest()
                ->where('month', '=', $month)
                ->where('year', '=', $year)
                ->get();
        }

        // Executives SR
        if ($type === 'executive') {
            if ($all_time) {
                return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                    ->latest()
                    ->where('employee_id', '=', $employee_id)
                    ->get();
            }

            return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                ->latest()
                ->where('employee_id', '=', $employee_id)
                ->where('year', '=', $year)
                ->get();
        }

        // Station SR
        if ($type === 'station') {
            if ($all_time) {
                return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                    ->latest()
                    ->where('station', '=', $station)
                    ->get();
            }

            return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                ->latest()
                ->where('station', '=', $station)
                ->where('year', '=', $year)
                ->get();
        }

        // Direct Agencies
        if ($type === 'direct') {
            return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                ->latest()
                ->where('agency_id', '=', 1)
                ->where('year', '=', $year)
                ->get();
        }

        // Agency SR
        if ($type === 'agency') {
            if ($all_time) {
                return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                    ->latest()
                    ->where('agency_id', '=', $agency_id)
                    ->get();
            }

            return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                ->latest()
                ->where('agency_id', '=', $agency_id)
                ->where('year', '=', $year)
                ->get();
        }

        // Advertiser SR
        if ($type === 'advertiser') {
            if ($all_time) {
                return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                    ->latest()
                    ->where('advertiser_id', '=', $advertiser_id)
                    ->get();
            }

            return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                ->latest()
                ->where('advertiser_id', '=', $advertiser_id)
                ->where('year', '=', $year)
                ->get();
        }

        // quarterly sales
        if ($type === 'quarterly') {
            return Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee', 'Logs')
                ->where('year', '=', $year)
                ->whereIn('month', $this->getQuarterMonths($quarter))
                ->orderBy('month')
                ->get();
        }
    }

    protected function getQuarterMonths($quarter)
    {
        $quarters = [
            '1' => ['01', '02', '03'],
            '2' => ['04', '05', '06'],
            '3' => ['07', '08', '09'],
            '4' => ['10', '11', '12'],
        ];

        return $quarters[(string) $quarter] ?? [];
    }

    /**
     * For modifying the contract's data.
     *
     * @return mixed
     */
    public function processContract($contracts)
    {
        foreach ($contracts as $contract) {
            $contract['station'] = $this->getStations($contract['station']);

            if ($contract->is_printed === 1) {
                $contract->print_status = "<div class='badge badge-success text-center'>Printed</div>";
            } elseif ($contract->is_printed === 0) {
                $contract->print_status = "<div class='badge badge-danger text-center'>Pending</div>";
            }

            if ($contract->advertiser_id === 0) {
                $contract->advertiser_name = '<div class="badge badge-danger text-center">Undefined</div>';
            } else {
                $contract->advertiser_name = $contract->Advertiser->name;
            }

            if ($contract->agency_id === 0) {
                $contract->agency_name = '<div class="badge badge-danger text-center">Undefined</div>';
            } else {
                $contract->agency_name = $contract->Agency->name;
            }

            $contract->short_contract_number = Str::limit($contract->contract_number, '20');

            $contract->short_bo_number = Str::limit($contract->bo_number, '15');

            if ($contract->bo_type == 'child') {
                $contract->short_parent_bo = Str::limit($contract->parent_bo, '15');
            }

            if (! $contract->Employee->middle_name) {
                $contract->employee_name = $contract->Employee->first_name[0].$contract->Employee->last_name[0];
            } else {
                $contract->employee_name = $contract->Employee->first_name[0].$contract->Employee->middle_name[0].$contract->Employee->last_name[0];
            }
        }

        return $contracts;
    }

    /**
     * @return Builder[]|Collection
     */
    public function getContractsByExecutives($executive, $year, $contract_type)
    {
        $contracts = Contract::with('Agency', 'Advertiser', 'Employee')
            ->where('bo_type', '=', 'normal')
            ->where('is_active', '=', '1')
            ->where('employee_id', '=', $executive)
            ->whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($contract_type === 'inactive') {
            $contracts = Contract::with('Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'normal')
                ->where('is_active', '=', '0')
                ->where('employee_id', '=', $executive)
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'parent') {
            $contracts = Contract::with('Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'parent')
                ->where('is_active', '=', '1')
                ->where('employee_id', '=', $executive)
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'inactive_parent') {
            $contracts = Contract::with('Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'parent')
                ->where('is_active', '=', '0')
                ->where('employee_id', '=', $executive)
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'child') {
            $contracts = Contract::with('Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'child')
                ->where('is_active', '=', '1')
                ->where('employee_id', '=', $executive)
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'inactive_child') {
            $contracts = Contract::with('Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'child')
                ->where('is_active', '=', '0')
                ->where('employee_id', '=', $executive)
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return $contracts;
    }

    /**
     * @return Builder[]|Collection
     */
    public function getContracts($year, $contract_type)
    {
        $contracts = Contract::with('Agency', 'Advertiser', 'Employee')
            ->where('bo_type', '=', 'normal')
            ->where('is_active', '=', '1')
            ->whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($contract_type === 'inactive') {
            $contracts = Contract::with('Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'normal')
                ->where('is_active', '=', '0')
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'parent') {
            $contracts = Contract::with('Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'parent')
                ->where('is_active', '=', '1')
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'inactive_parent') {
            $contracts = Contract::with('Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'parent')
                ->where('is_active', '=', '0')
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'child') {
            $contracts = Contract::with('Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'child')
                ->where('is_active', '=', '1')
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'inactive_child') {
            $contracts = Contract::with('Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'child')
                ->where('is_active', '=', '0')
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return $contracts;
    }

    /**
     * For modifying the contract's data.
     *
     * @return mixed
     */
    public function processContractSale($contracts, $filter, $user_level)
    {
        foreach ($contracts as $contract) {
            $contract['station'] = $this->getStations($contract['station']);

            // catching the advertiser / agency that has no value
            if ($contract->advertiser_id === 0) {
                $contract->advertiser_name = '<div class="badge badge-danger text-center">Undefined</div>';
            } else {
                $contract->advertiser_name = $contract->Advertiser->advertiser_name;
            }

            if ($contract->agency_id === 0) {
                $contract->agency_name = '<div class="badge badge-danger text-center">Undefined</div>';
            } else {
                $contract->agency_name = $contract->Agency->agency_name;
            }

            if (! $contract->Employee->middle_name[0]) {
                $contract->employee_name = $contract->Employee->first_name[0].$contract->Employee->last_name[0];
                $contract->executive_name = $contract->Employee->first_name.' '.$contract->Employee->last_name;
            } else {
                $contract->employee_name = $contract->Employee->first_name[0].$contract->Employee->middle_name[0].$contract->Employee->last_name[0];
                $contract->executive_name = $contract->Employee->first_name.' '.$contract->Employee->middle_name.' '.$contract->Employee->last_name;
            }

            foreach ($contract->Sale as $sale) {
                // getting the breakdown amount
                // 07/11/2023 - Always add total prod in breakdown amount as per Michelle Buna.
                // $contract->breakdown_amount = array_sum($sale->where('contract_id', $contract->id)->where('type', '!=', 'totalprod')->pluck('gross_amount')->toArray());
                $contract->breakdown_amount = array_sum($sale->where('contract_id', $contract->id)->pluck('gross_amount')->toArray());
                $contract->breakdown_prod = array_sum($sale->where('contract_id', $contract->id)->where('type', '=', 'totalprod')->pluck('gross_amount')->toArray());

                // getting the total amount of invoice
                $contract->invoice_amount = array_sum($sale->where('contract_id', $contract->id)->pluck('invoice_amount')->toArray()); // $sale->invoice_amount;

                // For getting invoice number to determine if the contract is billed, unbilled or has discrepancy.
                if ($sale->invoice_no) {
                    $hasDecimal = $this->hasDecimal($contract->breakdown_amount);
                    $breakdownAmount = $contract->breakdown_amount;

                    // if the amount does not have decimal places, use ceil to round off the nearest hundredth or round to the nearest integer
                    if (! $hasDecimal) {
                        $breakdownAmount = ceil($breakdownAmount / 100) * 100;
                    } else {
                        $breakdownAmount = round($breakdownAmount);
                    }

                    if ($breakdownAmount != $contract->invoice_amount) {
                        $contract->invoice_info = 'discrepancy';
                    } else {
                        $contract->invoice_info = 'billed';
                    }
                } elseif ($sale->invoice_no == '') {
                    $contract->invoice_info = 'unbilled';
                }

                $contract->short_bo_number = Str::limit($contract->bo_number, '20');
                $contract->short_parent_bo = Str::limit($contract->parent_bo, '15');

                $sale->type = $this->translateSaleType($sale->type);
            }
        }

        return $contracts;
    }

    /**
     * @return Builder[]|Collection
     */
    public function getSalesByExecutives($executive, $year, $contract_type)
    {
        $contracts = Contract::has('Sale')
            ->with('Sale.Contract', 'Sale.Logs', 'Agency', 'Advertiser', 'Employee')
            ->where('employee_id', '=', $executive)
            ->where('is_active', '=', '1')
            ->whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($contract_type === 'inactive') {
            $contracts = Contract::has('Sale')
                ->with('Sale.Contract', 'Sale.Logs', 'Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'normal')
                ->orWhere('bo_type', '=', 'parent')
                ->where('is_active', '=', '0')
                ->where('employee_id', '=', $executive)
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'parent') {
            $contracts = Contract::has('Sale')
                ->with('Sale.Contract', 'Sale.Logs', 'Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'parent')
                ->where('employee_id', '=', $executive)
                ->where('is_active', '=', '1')
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'inactive_parent') {
            $contracts = Contract::has('Sale')
                ->with('Sale.Contract', 'Sale.Logs', 'Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'parent')
                ->where('employee_id', '=', $executive)
                ->where('is_active', '=', '0')
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'child_bo') {
            $contracts = Contract::has('Sale')
                ->with('Sale.Contract', 'Sale.Logs', 'Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'child')
                ->where('is_active', '=', '1')
                ->where('employee_id', '=', $executive)
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'inactive_child_bo') {
            $contracts = Contract::has('Sale')
                ->with('Sale.Contract', 'Sale.Logs', 'Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'child')
                ->where('is_active', '=', '0')
                ->where('employee_id', '=', $executive)
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return $contracts;
    }

    /**
     * @return Builder[]|Collection
     */
    public function getSales($year, $contract_type)
    {
        $contracts = Contract::has('Sale')
            ->with('Sale.Contract', 'Sale.Logs', 'Agency', 'Advertiser', 'Employee')
            ->where('bo_type', '=', 'normal')
            ->where('is_active', '=', '1')
            ->whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($contract_type === 'inactive') {
            $contracts = Contract::has('Sale')
                ->with('Sale.Contract', 'Sale.Logs', 'Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'normal')
                ->where('is_active', '=', '0')
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'parent') {
            $contracts = Contract::has('Sale')
                ->with('Sale.Contract', 'Sale.Logs', 'Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'parent')
                ->where('is_active', '=', '1')
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'inactive_parent') {
            $contracts = Contract::has('Sale')
                ->with('Sale.Contract', 'Sale.Logs', 'Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'parent')
                ->where('is_active', '=', '0')
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'child_bo') {
            $contracts = Contract::has('Sale')
                ->with('Sale.Contract', 'Sale.Logs', 'Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'child')
                ->where('is_active', '=', '1')
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($contract_type === 'inactive_child_bo') {
            $contracts = Contract::has('Sale')
                ->with('Sale.Contract', 'Sale.Logs', 'Agency', 'Advertiser', 'Employee')
                ->where('bo_type', '=', 'child')
                ->where('is_active', '=', '0')
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return $contracts;
    }

    public function getAvailableYearsPerStation($station)
    {
        $contract_sales = Sale::with('Contract', 'Agency', 'Advertiser', 'Employee')
            ->selectRaw('contract_id, agency_id, advertiser_id, employee_id, sum(gross_amount) as gross_sales, month, year, created_at')
            ->where('station', '=', $station)
            ->whereHas('Contract', function (Builder $query) {
                $query->where('bo_type', '=', 'normal')
                    ->where('is_active', '=', 1);
            })->orderBy('month')
            ->orderBy('year', 'desc')
            ->groupBy(['contract_id', 'agency_id', 'advertiser_id', 'employee_id', 'month', 'year', 'created_at'])
            ->get();

        $years = $contract_sales->groupBy('year');

        $years_data = [];

        foreach ($years as $year => $data) {
            $years_data[] = $year;
        }

        return $years_data;
    }
}

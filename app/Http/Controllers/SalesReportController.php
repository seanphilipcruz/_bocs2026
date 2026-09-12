<?php

namespace App\Http\Controllers;

use App\Http\Traits\LogTrait;
use App\Http\Traits\ProcessorTrait;
use App\Http\Traits\SystemDefaultsTrait;
use App\Models\Advertiser;
use App\Models\Agency;
use App\Models\Employee;
use App\Models\Sale;
use Auth;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    use LogTrait;
    use ProcessorTrait;
    use SystemDefaultsTrait;

    public function index(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:annual,monthly,station,executive,agencies,advertisers,quarterly',
            'employee_id' => 'nullable|integer|exists:employees,id',
            'agency_id' => 'nullable|integer|exists:agencies,id',
            'advertiser_id' => 'nullable|integer|exists:advertisers,id',
            'station' => 'nullable|string|max:255',
            'month' => ['nullable', 'regex:/^(0?[1-9]|1[0-2])$/'],
            'year' => 'nullable|integer|digits:4',
            'quarter' => 'nullable|in:1,2,3,4',
            'sort' => 'nullable|in:true,false,1,0',
            'is_direct' => 'nullable|in:true,false,1,0',
        ]);
        $this->normalizeMonthInput($request);

        $user_level = Auth::user()->Job->level;
        $executive = Auth::user();
        $executive_id = Auth::id();
        $can_filter_executives = in_array((string) $user_level, ['0', '1'], true);

        $sr_type = ! $request['type'] ? 'executive' : $request['type'];
        $employee_id = $can_filter_executives && $request['employee_id']
            ? $request['employee_id']
            : $executive_id;
        $station = $request['station'];
        $agency_id = $request['agency_id'];
        $advertiser_id = $request['advertiser_id'];
        $quarter = $request['quarter'];
        $isDirect = $this->requestBoolean($request, 'is_direct');

        $period_query = Sale::query();

        if ($user_level === '2') {
            $period_query->where('employee_id', $executive_id);
        } elseif ($sr_type === 'executive') {
            $period_query->where('employee_id', $employee_id);
        }

        if ($sr_type === 'agencies' && $agency_id) {
            $period_query->where('agency_id', $agency_id);
        }

        if ($sr_type === 'advertisers' && $advertiser_id) {
            $period_query->where('advertiser_id', $advertiser_id);
        }

        if ($sr_type === 'station' && $station) {
            $period_query->where('station', $station);
        }

        if ($isDirect) {
            $period_query->where('agency_id', 1);
        }

        $yearly_sales = (clone $period_query)
            ->select('year')
            ->orderBy('year', 'desc')
            ->groupBy('year')
            ->pluck('year');

        $latest_sale = (clone $period_query)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first(['month', 'year']);

        $executives_query = Employee::query()
            ->where('is_active', 1)
            ->whereHas('Job', function ($query) {
                $query->where('level', '!=', '3');
            })
            ->orderBy('first_name')
            ->orderBy('last_name');

        if (! $can_filter_executives) {
            $executives_query->where('id', $executive_id);
        }

        $executives = $executives_query->get();

        $agencies = Agency::with('Logs')
            ->has('Sale')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        $advertisers = Advertiser::with('Logs')
            ->has('Sale')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        foreach ($executives as $employee) {
            $employee->account_executive = $employee->first_name.' '.$employee->last_name;
        }

        $selected_executive = $employee_id === $executive_id
            ? $executive
            : Employee::findOrFail($employee_id);
        $executive_report_title = trim($selected_executive->first_name.' '.$selected_executive->last_name);

        if (! $latest_sale) {
            return response()->json([
                'sales_report' => [],
                'gross_sales' => 0,
                'sale_years' => $yearly_sales,
                'sr_for' => $sr_type === 'executive' ? $executive_report_title : 'Monthly',
                'month' => null,
                'year' => null,
                'employee_id' => $employee_id,
                'executives' => $executives,
                'agencies' => $agencies,
                'advertisers' => $advertisers,
            ]);
        }

        $latest_month = $latest_sale->month;
        $latest_year = $latest_sale->year;

        $year = $request['year'] ?: $latest_year;
        $latest_month_for_year = (clone $period_query)
            ->where('year', $year)
            ->orderByDesc('month')
            ->value('month');
        $month = $request['month'] ?: ($latest_month_for_year ?: $latest_month);

        $request->merge([
            'month' => $month,
            'year' => $year,
        ]);

        $query = $this->requestBoolean($request, 'sort');

        $sales_report = Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee')
            ->latest()
            ->where('month', '=', $latest_month)
            ->where('year', '=', $latest_year)
            ->get();

        $sr = 'Monthly';

        // if the query is not set, load this year's sales report.
        if ($query) {
            // Executive Specific SR
            if ($user_level === '2') {
                $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                $sales_report = $this->getExecutiveSalesReport($request, 'executive');

                $employee = Employee::with('Logs')->findOrFail($executive->id);

                $sr = $employee->first_name.' '.$employee->last_name;
                $year = $request['year'];

                // Annual SR
                if ($sr_type === 'annual') {
                    $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                    $sales_report = $this->getExecutiveSalesReport($request, 'yearly');

                    $sr = $request['year'];
                }

                if ($sr_type === 'monthly') {
                    $month = ! $request['month'] ? $latest_month : $request['month'];
                    $request['month'] = ! $request['month'] ? $latest_month : $request['month'];
                    $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                    $sales_report = $this->getExecutiveSalesReport($request, 'monthly');

                    $sr = $this->convertNumberToDate($month);
                    $year = $request['year'];
                }

                if ($sr_type === 'station') {
                    $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                    $sales_report = $this->getExecutiveSalesReport($request, 'station');

                    $sr = $station;
                    $year = $request['year'];
                }

                if ($sr_type === 'agencies') {
                    $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                    $sales_report = $this->getExecutiveSalesReport($request, 'agency');

                    $agency = Agency::with('Logs')->findOrFail($agency_id);

                    $sr = $agency->name;
                    $year = $request['year'];
                }

                if ($sr_type === 'advertisers') {
                    $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                    $sales_report = $this->getExecutiveSalesReport($request, 'advertiser');

                    $advertiser = Advertiser::with('Logs')->findOrFail($advertiser_id);

                    $sr = $advertiser->name;
                    $year = $request['year'];
                }

                if ($sr_type === 'quarterly') {
                    $request['quarter'] = ! $request['quarter'] ? '1' : $request['quarter'];
                    $quarter = ! $request['quarter'] ? '1' : $request['quarter'];
                    $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                    $sales_report = $this->getExecutiveSalesReport($request, 'quarterly');
                    $sr = $quarter === '1' ? 'First Quarter' :
                        ($quarter === '2' ? 'Second Quarter' :
                            ($quarter === '3' ? 'Third Quarter' :
                                ($quarter === '4' ? 'Fourth Quarter' : 'Invalid Quarter')));

                    $year = $request['year'];
                }

                if ($isDirect) {
                    $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                    $sales_report = $this->getExecutiveSalesReport($request, 'direct');

                    $sr = 'Direct Accounts';
                    $year = $request['year'];
                }
            } else {
                // Annual SR
                if ($sr_type === 'annual') {
                    $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                    $sales_report = $this->getSalesReport($request, 'yearly');

                    // if the request has station
                    if (isset($station)) {
                        $sr = ($station == 'manila' ? 'Manila' : ($station == 'cebu' ? 'Cebu' : ($station == 'davao' ? 'Davao' : 'Error'))).' '.$request['year'];
                    } else {
                        $sr = $request['year'];
                    }
                }

                if ($sr_type === 'monthly') {
                    $month = ! $request['month'] ? $latest_month : $request['month'];
                    $request['month'] = ! $request['month'] ? $latest_month : $request['month'];
                    $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                    $sales_report = $this->getSalesReport($request, 'monthly');

                    $sr = $this->convertNumberToDate($month);
                    $year = $request['year'];
                }

                if ($sr_type === 'station') {
                    $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                    $sales_report = $this->getSalesReport($request, 'station');

                    $sr = $station;
                    $year = $request['year'];
                }

                if ($sr_type === 'executive') {
                    // set the year
                    $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                    $sales_report = $this->getSalesReport($request, 'executive');

                    $employee = Employee::with('Logs')->findOrFail($employee_id);

                    $sr = $employee->first_name.' '.$employee->last_name;
                    $year = $request['year'];
                }

                if ($sr_type === 'agencies') {
                    $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                    $sales_report = $this->getSalesReport($request, 'agency');

                    $agency = Agency::with('Logs')->findOrFail($agency_id);

                    $sr = $agency->name;
                    $year = $request['year'];
                }

                if ($sr_type === 'advertisers') {
                    $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                    $sales_report = $this->getSalesReport($request, 'advertiser');

                    $advertiser = Advertiser::with('Logs')->findOrFail($advertiser_id);

                    $sr = $advertiser->name;
                    $year = $request['year'];
                }

                if ($sr_type === 'quarterly') {
                    $request['quarter'] = ! $request['quarter'] ? '1' : $request['quarter'];
                    $quarter = ! $request['quarter'] ? '1' : $request['quarter'];
                    $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                    $sales_report = $this->getSalesReport($request, 'quarterly');
                    $sr = $quarter === '1' ? 'First Quarter' :
                        ($quarter === '2' ? 'Second Quarter' :
                            ($quarter === '3' ? 'Third Quarter' :
                                ($quarter === '4' ? 'Fourth Quarter' : 'Invalid Quarter')));

                    $year = $request['year'];
                }

                if ($isDirect) {
                    $request['year'] = ! $request['year'] ? $latest_year : $request['year'];

                    $sales_report = $this->getSalesReport($request, 'direct');

                    $sr = 'Direct Accounts';
                    $year = $request['year'];
                }
            }
        }

        foreach ($sales_report as $sale) {
            $sale->month_name = $this->convertNumberToDate($sale->month);
            $sale->sale_type = $this->translateSaleType($sale->type);
            $employee = optional(optional($sale->Contract)->Employee);
            $sale->account_executive = trim($employee->first_name.' '.$employee->last_name);
            $sale->adjustment = round((float) $sale->gross_amount - (float) $sale->invoice_amount, 2);
        }

        $gross_sales = round($sales_report->sum('gross_amount'), 2);

        return response()->json([
            'sales_report' => $sales_report,
            'gross_sales' => $gross_sales,
            'sale_years' => $yearly_sales,
            'sr_for' => $sr,
            'month' => $month,
            'year' => $year,
            'employee_id' => $employee_id,
            'executives' => $executives,
            'agencies' => $agencies,
            'advertisers' => $advertisers,
        ]);
    }

    public function getMonthly(Request $request)
    {
        $request->validate([
            'filter' => 'nullable|in:true,false,1,0',
            'refresh' => 'nullable|in:true,false,1,0',
            'station' => 'nullable|string|max:255',
            'month' => ['nullable', 'regex:/^(0?[1-9]|1[0-2])$/'],
            'year' => 'nullable|integer|digits:4',
        ]);
        $this->normalizeMonthInput($request);

        $filter = $this->requestBoolean($request, 'filter');
        $refresh = $this->requestBoolean($request, 'refresh');
        $station = $request['station'] ? $request['station'] : 'Manila';

        $latest_sale = Sale::query()
            ->where('station', '=', $station)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->first(['month', 'year']);

        $contracts_sales = Sale::query()
            ->select('id', 'year', 'created_at')
            ->orderBy('year', 'desc')
            ->get();

        foreach ($contracts_sales as $contracts_sale) {
            $contracts_sale->months = date('m', strtotime($contracts_sale->created_at));
        }

        $months = $contracts_sales->groupBy('months');
        $years = $contracts_sales->groupBy('year');

        if (! $latest_sale) {
            if ($refresh) {
                return response()->json([
                    'sales_reports' => [],
                    'gross_sales' => 'Gross Sales: <div class="text-primary h3">₱0.00</div>',
                ]);
            }

            return response()->json([
                'sales_report' => [],
                'gross_sales' => 0,
                'months' => $months,
                'years' => $years,
                'month' => null,
                'year' => null,
                'filter' => $filter,
                'latest_sale_month' => null,
            ]);
        }

        $latest_sale_month = $latest_sale->month;
        $latest_sale_year = $latest_sale->year;

        $sales_report = Sale::query()
            ->selectRaw('month, sum(gross_amount) as gross_sales, year, station')
            ->where('station', '=', $station)
            ->where('year', $latest_sale_year)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->groupBy(['month', 'year', 'station'])
            ->get();

        // if no "year" parameter in the request, use the latest year instead of date('Y') function
        $month = $request['month'] ? $request['month'] : $latest_sale_month;
        $year = $request['year'] ? $request['year'] : $latest_sale_year;

        $gross_sales = Sale::query()
            ->where('station', '=', $station)
            ->where('month', $latest_sale_month)
            ->where('year', $latest_sale_year)
            ->sum('gross_amount');

        if ($filter) {
            $sales_report = Sale::query()
                ->selectRaw('month, sum(gross_amount) as gross_sales, year, station')
                ->where('station', '=', $station)
                ->where('year', $year)
                ->orderBy('year', 'desc')
                ->orderBy('month')
                ->groupBy(['month', 'year', 'station'])
                ->get();

            $gross_sales = Sale::query()
                ->where('station', '=', $station)
                ->where('month', $month)
                ->where('year', $year)
                ->sum('gross_amount');
        }

        foreach ($sales_report as $sales) {
            $sales->month = $sales->month.' - '.$this->convertNumberToDate($sales->month);
            $sales->gross_sales = ! $sales->gross_sales ? '₱'.'0.00' : '₱'.number_format($sales->gross_sales, 2);
        }

        if ($refresh) {
            $gross_sales = ! $gross_sales
                ? $month.' Gross Sales: <div class="text-primary h3">₱0.00</div>'
                : $month.' Gross Sales: <div class="text-primary h3">₱'.number_format($gross_sales, 2).'</div>';

            return response()->json(['sales_reports' => $sales_report, 'gross_sales' => $gross_sales]);
        }

        return response()->json([
            'sales_report' => $sales_report,
            'gross_sales' => $gross_sales,
            'months' => $months,
            'years' => $years,
            'month' => $month,
            'year' => $year,
            'filter' => $filter,
            'latest_sale_month' => $latest_sale_month,
        ]);
    }

    public function getExecutives(Request $request)
    {
        $request->validate([
            'refresh' => 'nullable|in:true,false,1,0',
        ]);

        $latest_sale = Sale::query()
            ->orderBy('created_at', 'desc')
            ->first(['year']);

        if (! $latest_sale) {
            return response()->json([
                'sales_report' => [],
                'gross_sales' => '0.00',
            ]);
        }

        $latest_sale_year = $latest_sale->year;

        $sales_report = Sale::with('Employee')
            ->selectRaw('month, year, employee_id, sum(gross_amount) as gross_sales')
            ->where('year', $latest_sale_year)
            ->orderBy('year', 'desc')
            ->orderBy('month')
            ->groupBy(['month', 'year', 'employee_id'])
            ->get();

        foreach ($sales_report as $sales) {
            $sales->month = $sales->month.' - '.$this->convertNumberToDate($sales->month);
            $sales->gross_sales = ! $sales->gross_sales ? '₱'.'0.00' : '₱'.number_format($sales->gross_sales, 2);
        }

        $gross_sales = number_format(Sale::query()
            ->where('year', $latest_sale_year)
            ->sum('gross_amount'), 2);

        foreach ($sales_report as $sales) {
            if (! $sales->Employee) {
                $sales->name = '';
            } elseif (! $sales->Employee->middle_name) {
                $sales->name = $sales->Employee->first_name.' '.$sales->Employee->last_name;
            } else {
                $sales->name = $sales->Employee->first_name.' '.$sales->Employee->middle_name[0].'. '.$sales->Employee->last_name;
            }
        }

        if ($this->requestBoolean($request, 'refresh')) {
            return response()->json([
                'sales_report' => $sales_report,
            ]);
        }

        return response()->json([
            'sales_report' => $sales_report,
            'gross_sales' => $gross_sales,
        ]);
    }

    public function getAccounting(Request $request)
    {
        $request->validate([
            'station' => 'nullable|string|max:255',
            'month' => ['nullable', 'regex:/^(0?[1-9]|1[0-2])$/'],
            'year' => 'nullable|integer|digits:4',
        ]);
        $this->normalizeMonthInput($request);

        $station = $request['station'] == null ? 'Manila' : $request['station'];

        $latest_sale = Sale::query()
            ->latest()
            ->whereNotNull('invoice_no')
            ->where('station', '=', $station)
            ->first(['month', 'year']);

        // if there are no sales with invoice number, load default.
        if (! $latest_sale) {
            $latest_sale = Sale::query()
                ->latest()
                ->where('station', '=', $station)
                ->first(['month', 'year']);
        }

        $years_data = $this->getAvailableYearsPerStation($station);

        if (! $latest_sale) {
            return response()->json([
                'years' => $years_data,
                'station' => $station,
                'sales_report' => [],
                'traffic_gross_sales' => 0,
                'national_sales' => 0,
                'difference' => 0,
                'cash' => 0,
                'ex_deal' => 0,
                'percentage' => '0.00',
                'month_name' => null,
            ]);
        }

        $latest_sale_month = $latest_sale->month;
        $latest_sale_year = $latest_sale->year;

        // if there's no "year" parameter in the request, use the latest_sale_month/year value.
        $month = ! $request['month'] ? $latest_sale_month : $request['month'];
        $year = ! $request['year'] ? $latest_sale_year : $request['year'];

        // get current traffic sales report
        $traffic_sales_report = Sale::with('Contract.Sale', 'Agency', 'Advertiser', 'Employee')
            ->where('station', '=', $station)
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('month', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // get the ones with the invoice number for accounting side computation
        $cash = Sale::query()
            ->whereNotNull('invoice_no')
            ->where('station', '=', $station)
            ->where('transaction', '=', 'Cash')
            ->where('month', '=', $month)
            ->where('year', '=', $year)
            ->sum('gross_amount');

        $ex_deal = Sale::query()
            ->whereNotNull('invoice_no')
            ->where('station', '=', $station)
            ->where('transaction', '=', 'Ex-deal')
            ->where('month', '=', $month)
            ->where('year', '=', $year)
            ->sum('gross_amount');

        $sub_total_accounting = $ex_deal + $cash;

        // national sale is the ones with the invoice_number and date.
        $national_sales = round($sub_total_accounting, 2);

        foreach ($traffic_sales_report as $sale) {
            if (! $sale->Contract) {
                $sale->breakdown_amount = 0;
                $sale->invoice_amount = 0;
                $sale->invoice_numbers = '';
                $sale->invoice_dates = '';
                $sale->invoice_info = 'unbilled';
                $sale->start_of_broadcast = null;
                $sale->end_of_broadcast = null;
                $sale->account_executive = $sale->Employee ? $sale->Employee->first_name.' '.$sale->Employee->last_name : '';

                continue;
            }

            $month_start = date('m', strtotime($sale->Contract->commencement));
            $month_end = date('m', strtotime($sale->Contract->end_of_broadcast));
            $year_start = date('Y', strtotime($sale->Contract->commencement));
            $year_end = date('Y', strtotime($sale->Contract->end_of_broadcast));
            $invoice_numbers = implode(', ', $sale->Contract->Sale->whereNotNull('invoice_no')->pluck('invoice_no')->toArray());
            $billing_dates = implode(', ', $sale->Contract->Sale->whereNotNull('invoice_date')->pluck('invoice_date')->toArray());

            // getting the breakdown amount
            $sale->breakdown_amount = array_sum($sale->Contract->Sale->pluck('gross_amount')->toArray());
            // getting the total amount of invoice
            $sale->invoice_amount = array_sum($sale->Contract->Sale->pluck('invoice_amount')->toArray());
            $sale->invoice_numbers = $invoice_numbers;
            $sale->invoice_dates = $billing_dates;

            // getting the invoice information
            // For getting invoice number to determine if the contract is billed, unbilled or has discrepancy.
            if ($sale->invoice_no) {
                $hasDecimal = $this->hasDecimal($sale->breakdown_amount);
                $breakdownAmount = $sale->breakdown_amount;

                // if the amount does not have decimal places, use ceil to round off the nearest hundredth or round to the nearest integer
                if (! $hasDecimal) {
                    $breakdownAmount = ceil($breakdownAmount / 100) * 100;
                } else {
                    $breakdownAmount = round($breakdownAmount);
                }

                if ($breakdownAmount != $sale->invoice_amount) {
                    $sale->invoice_info = 'discrepancy';
                } else {
                    $sale->invoice_info = 'billed';
                }
            } elseif ($sale->invoice_no == '') {
                $sale->invoice_info = 'unbilled';
            }

            if ($month_start === $month_end && $year_start === $year_end) {
                $sale->start_of_broadcast = date('M d', strtotime($sale->Contract->commencement));
                $sale->end_of_broadcast = date('d, Y', strtotime($sale->Contract->end_of_broadcast));
            }

            if ($month_start !== $month_end && $year_start === $year_end) {
                $sale->start_of_broadcast = date('M d', strtotime($sale->Contract->commencement));
                $sale->end_of_broadcast = date('M d, Y', strtotime($sale->Contract->end_of_broadcast));
            }

            if ($year_start !== $year_end) {
                $sale->start_of_broadcast = date('M d, Y', strtotime($sale->Contract->commencement));
                $sale->end_of_broadcast = date('M d, Y', strtotime($sale->Contract->end_of_broadcast));
            }

            $sale->account_executive = $sale->Employee ? $sale->Employee->first_name.' '.$sale->Employee->last_name : '';
        }

        // Traffic sales report represents the numbers that have been encoded by traffic
        $traffic_gross_sales = round($traffic_sales_report->sum('gross_amount'), 2);

        // Credit
        $credit = $traffic_gross_sales - $national_sales;

        $percentage = $traffic_gross_sales > 0
            ? number_format($national_sales / $traffic_gross_sales * 100, 2)
            : '0.00';

        $month_name = $this->convertNumberToDate($month);

        return response()->json([
            'years' => $years_data,
            'station' => $station,
            'sales_report' => $traffic_sales_report,
            'traffic_gross_sales' => $traffic_gross_sales,
            'national_sales' => $national_sales,
            'difference' => $credit,
            'cash' => $cash,
            'ex_deal' => $ex_deal,
            'percentage' => $percentage,
            'month_name' => $month_name,
        ]);
    }

    private function requestBoolean(Request $request, string $key): bool
    {
        return filter_var($request->input($key, false), FILTER_VALIDATE_BOOLEAN);
    }

    private function normalizeMonthInput(Request $request): void
    {
        if ($request->filled('month')) {
            $request->merge([
                'month' => str_pad($request->input('month'), 2, '0', STR_PAD_LEFT),
            ]);
        }
    }
}

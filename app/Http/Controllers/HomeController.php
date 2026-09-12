<?php

namespace App\Http\Controllers;

use App\Http\Traits\GeneratorTrait;
use App\Http\Traits\ProcessorTrait;
use App\Models\Description;
use App\Models\Sale;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    use GeneratorTrait;
    use ProcessorTrait;

    public function index(Request $request)
    {
        $user_level = Auth::user()->Job->level;

        $sale = Sale::with('Logs')
            ->orderBy('year', 'desc')
            ->get();

        $latest_sale_year = $sale->first()->year;

        $request['year'] = ! $request['year'] ? $latest_sale_year : $request['year'];

        $station = $request['location'];
        $year = ! $request['year'] ? $latest_sale_year : $request['year'];

        if ($user_level === '3') {
            return response()->json([
                'months' => [],
                'executive_sales' => [],
                'yearly_sales' => [],
                'monthly_executive_sales' => [],
                'advertisers' => [],
                'agency' => [],
                'direct' => [],
                'product' => [],
                'report_previews' => $this->getDashboardReportPreviews($station),
                'responsibilities' => $this->getDescriptions(),
            ]);
        }

        if ($user_level === '2') {
            return $this->getHomeByExecutives($station, $year);
        }

        $sales = Sale::query()
            ->with('Logs')
            ->selectRaw('month, year, sum(gross_amount) as gross_sales')
            ->where('station', $station)
            ->where('year', $year)
            ->orderBy('year')
            ->orderBy('month')
            ->groupBy('month', 'year')
            ->get();

        foreach ($sales as $sale) {
            $sale->month_name = $this->convertNumberToDate($sale->month);
        }

        // for manila chart data
        $months = $sales->pluck('month_name');
        $gross_sales = $sales->pluck('gross_sales', 'month_name')->toArray();

        // Account executives
        $account_executives = Sale::with('Logs')
            ->selectRaw('employee_id, sum(gross_amount) as ae_sales')
            ->where('station', '=', $station)
            ->where('year', '=', $year)
            ->groupBy('employee_id')
            ->get();

        $bar_ae = [];
        $bar_data = [];

        foreach ($account_executives as $employee) {
            $employee->executive_name = $employee->Employee->first_name.' '.$employee->Employee->last_name;

            if ($employee->Employee->color == null) {
                $employee->color = '#00c9ff';
            } else {
                $employee->color = $employee->Employee->color;
            }

            $bar_ae['executive_name'] = $employee->executive_name;
            $bar_ae['color'] = $employee->color;
            $bar_ae['sales'] = $employee->ae_sales;

            $bar_data[] = $bar_ae;
        }

        // yearly sales
        $yearly_sales = Sale::with('Logs')
            ->selectRaw('year, sum(gross_amount) as yearly_sales')
            ->where('station', '=', $station)
            ->orderBy('year')
            ->groupBy('year')
            ->get();

        $chart_year = [];
        $chart_year_data = [];

        foreach ($yearly_sales as $yearly_sale) {
            $chart_year['years'] = $yearly_sale->year;
            $chart_year['color'] = $this->generateRandomColor();
            $chart_year['sales'] = $yearly_sale->yearly_sales;

            $chart_year_data[] = $chart_year;
        }

        // Account Executive Sales in Charts
        $monthlyExecutiveSales = Sale::with('Logs')
            ->selectRaw('month, year, sum(gross_amount) as monthly_ae_sales, employee_id')
            ->where('station', '=', $station)
            ->where('year', '=', $year)
            ->orderBy('year')
            ->orderBy('month')
            ->groupBy('month', 'year', 'employee_id')
            ->get();

        foreach ($monthlyExecutiveSales as $sales) {
            $sales->month_name = $this->convertNumberToDate($sales->month);

            $sales->executive_name = $sales->Employee->first_name.' '.$sales->Employee->last_name;

            if ($sales->Employee->color == null) {
                $sales->color = '#00c9ff';
            } else {
                $sales->color = $sales->Employee->color;
            }
        }

        $executiveNames = $monthlyExecutiveSales->groupBy('executive_name');

        $chart_data = [];

        // for Manila Sales
        $chart_ae['executive'] = $station === 'Manila' ? 'Manila' : ($station === 'Cebu' ? 'Cebu' : 'Davao').' Sales';
        $chart_ae['color'] = '#00c9ff';
        $chart_ae['sales'] = $gross_sales;

        $chart_data[] = $chart_ae;

        // for Executives
        foreach ($executiveNames as $executive => $sales) {
            $chart_ae['executive'] = $executive;
            $chart_ae['color'] = $sales->pluck('color')->first();
            $chart_ae['sales'] = $sales->pluck('monthly_ae_sales', 'month_name'); // $sales->pluck('monthly_ae_sales');

            $chart_data[] = $chart_ae;
        }

        // getting the top 10
        $ad_sales = [];
        $advertiser_data = [];

        $advertiser_sales = Sale::with('Advertiser', 'Employee')
            ->selectRaw('advertiser_id, employee_id, year, sum(gross_amount) as advertiser_sales')
            ->where('year', '=', $year)
            ->where('station', '=', $station)
            ->orderBy('advertiser_sales', 'desc')
            ->groupBy(['advertiser_id', 'employee_id', 'year'])
            ->get()
            ->take(10);

        $advertisers = $advertiser_sales->groupBy('advertiser_id');

        foreach ($advertisers as $key => $advertiser_sale) {
            $total_ad_sales = array_sum($advertiser_sale->pluck('advertiser_sales')->toArray());

            $ad_sales['id'] = $key;
            $ad_sales['year'] = $year;
            $ad_sales['sales'] = $total_ad_sales;

            foreach ($advertiser_sale as $sales) {
                $executive = $sales->Employee->first_name.' '.$sales->Employee->last_name;

                $ad_sales['name'] = $sales->Advertiser->name;
                $ad_sales['account_executive'] = $executive;
            }

            $advertiser_data[] = $ad_sales;
        }

        $ag_sales = [];
        $agency_data = [];

        $agency_sales = Sale::with('Agency', 'Employee')
            ->selectRaw('agency_id, employee_id, year, sum(gross_amount) as agency_sales')
            ->where('year', '=', $year)
            ->where('station', '=', $station)
            ->orderBy('agency_sales', 'desc')
            ->groupBy(['agency_id', 'employee_id', 'year'])
            ->get()
            ->take(10);

        $agency = $agency_sales->groupBy('agency_id');

        foreach ($agency as $key => $agency_sale) {
            $total_agency_sales = array_sum($agency_sale->pluck('agency_sales')->toArray());

            $ag_sales['id'] = $key;
            $ag_sales['year'] = $year;
            $ag_sales['sales'] = $total_agency_sales;

            foreach ($agency_sale as $sales) {
                $executive = $sales->Employee->first_name.' '.$sales->Employee->last_name;

                $ag_sales['name'] = $sales->Agency->name;
                $ag_sales['account_executive'] = $executive;
            }

            $agency_data[] = $ag_sales;
        }

        $direct_sales = [];
        $direct_agency_data = [];

        $direct_accounts = Sale::selectRaw('advertiser_id, agency_id, employee_id, year, sum(gross_amount) as direct_sales')
            ->where('station', '=', $station)
            ->where('year', '=', $year)
            ->where('agency_id', '=', 1)
            ->orderBy('direct_sales', 'desc')
            ->groupBy(['advertiser_id', 'agency_id', 'employee_id', 'year'])
            ->get()
            ->take(10);

        $direct = $direct_accounts->groupBy('advertiser_id');

        foreach ($direct as $key => $account) {
            $total_direct_sales = array_sum($account->pluck('direct_sales')->toArray());

            $direct_sales['advertiser_id'] = $key;
            $direct_sales['year'] = $year;
            $direct_sales['account_sale'] = $total_direct_sales;

            foreach ($account as $direct_account) {
                $direct_sales['advertiser_name'] = $direct_account->Advertiser->name;
                $direct_sales['account_executive'] = $direct_account->Employee->first_name.' '.$direct_account->Employee->last_name;
            }

            $direct_agency_data[] = $direct_sales;
        }

        $product_sales = [];
        $product_sale_data = [];

        $products = DB::table('sales')
            ->join('contracts', 'sales.contract_id', '=', 'contracts.id')
            ->join('employees', 'sales.employee_id', '=', 'employees.id')
            ->selectRaw("sales.contract_id, sales.agency_id, sales.advertiser_id,
            sum(sales.gross_amount) as product_sale, contracts.product as product_name, sales.employee_id,
            sales.year, concat(employees.first_name, ' ', employees.last_name) as account_executive")
            ->where('sales.year', '=', $year)
            ->where('sales.station', '=', $station)
            ->groupBy(['sales.contract_id', 'sales.agency_id', 'sales.advertiser_id', 'sales.employee_id', 'sales.year'])
            ->get()
            ->take(10);

        $product = $products->groupBy('product_name');

        foreach ($product as $key => $product_data) {
            $total_product_sales = array_sum($product_data->pluck('product_sale')->toArray());

            $product_sales['product_name'] = $key;
            $product_sales['year'] = $year;
            $product_sales['product_sales'] = $total_product_sales;

            foreach ($product_data as $product_sale) {
                $product_sales['account_executive'] = $product_sale->account_executive;
            }

            $product_sale_data[] = $product_sales;
        }

        return response()->json([
            'months' => $months,
            'executive_sales' => $bar_data,
            'yearly_sales' => $chart_year_data,
            'monthly_executive_sales' => $chart_data,
            'advertisers' => $advertiser_data,
            'agency' => $agency_data,
            'direct' => $direct_agency_data,
            'product' => $product_sale_data,
            'report_previews' => $this->getDashboardReportPreviews($station),
            'responsibilities' => $this->getDescriptions(),
        ]);
    }

    // getting the ranked data
    public function getRanked(Request $request)
    {
        $user_level = Auth::user()->Job->level;

        $sale = Sale::with('Logs')
            ->latest()
            ->get();

        $latest_month = $sale->first()->month;
        $latest_year = $sale->first()->year;

        // $month = !$request['month'] ? $request['month'] = $latest_month : $request['month'];
        $year = ! $request['year'] ? $request['year'] = $latest_year : $request['year'];
        $station = ! $request['station'] ? $request['station'] = 'Manila' : $request['station'];
        $type = ! $request['type'] ? $request['type'] = 'advertiser' : $request['type'];

        if ($user_level === '2') {
            return $this->getRankedByExecutives($type, $station, $year);
        }

        $yearly_sales = Sale::selectRaw('year, sum(gross_amount) as yearly_sales')
            ->where('station', '=', $station)
            ->orderBy('year', 'desc')
            ->groupBy('year')
            ->get();

        $sale_year = [];

        $years = $yearly_sales->groupBy('year');

        foreach ($years as $key => $yearly_sale) {
            $sale_year[] = $key;
        }

        $advertiser_rank = 0;
        $ranked_sales = [];
        $ranked = [];

        // default that's being get is ranked advertiser data. "Top Advertisers"
        $ranked_data = Sale::selectRaw('advertiser_id, employee_id, year, sum(gross_amount) as advertiser_sales')
            ->where('year', '=', $year)
            ->where('station', '=', $station)
            ->orderBy('advertiser_sales', 'desc')
            ->groupBy(['advertiser_id', 'employee_id', 'year'])
            ->get();

        $data = $ranked_data->groupBy('advertiser_id');

        foreach ($data as $key => $advertiser_sale) {
            $total_agency_sales = array_sum($advertiser_sale->pluck('advertiser_sales')->toArray());

            $ranked_sales['rank'] = $advertiser_rank + 1;
            $ranked_sales['id'] = $key;
            $ranked_sales['year'] = $year;
            $ranked_sales['sales'] = $total_agency_sales;

            foreach ($advertiser_sale as $sales) {
                $executive = $sales->Employee->first_name.' '.$sales->Employee->last_name;

                $ranked_sales['name'] = $sales->Advertiser->name;
                $ranked_sales['account_executive'] = $executive;
            }

            $ranked[] = $ranked_sales;

            $advertiser_rank++;
        }

        if ($type == 'agency') {
            $agency_rank = 0;
            $ranked_sales = [];
            $ranked = [];

            // default that's being get is ranked agency data. "Top Agency"
            $ranked_data = Sale::selectRaw('agency_id, employee_id, year, sum(gross_amount) as agency_sales')
                ->where('year', '=', $year)
                ->where('station', '=', $station)
                ->orderBy('agency_sales', 'desc')
                ->groupBy(['agency_id', 'employee_id', 'year'])
                ->get();

            $data = $ranked_data->groupBy('agency_id');

            foreach ($data as $key => $agency_sale) {
                $total_agency_sales = array_sum($agency_sale->pluck('agency_sales')->toArray());

                $ranked_sales['rank'] = $agency_rank + 1;
                $ranked_sales['id'] = $key;
                $ranked_sales['year'] = $year;
                $ranked_sales['sales'] = $total_agency_sales;

                foreach ($agency_sale as $sales) {
                    $executive = $sales->Employee->first_name.' '.$sales->Employee->last_name;

                    $ranked_sales['name'] = $sales->Agency->name;
                    $ranked_sales['account_executive'] = $executive;
                }

                $ranked[] = $ranked_sales;

                $agency_rank++;
            }
        }

        if ($type == 'direct') {
            $account_rank = 0;
            $ranked_sales = [];
            $ranked = [];

            // default that's being get is ranked agency data. "Top Agency"nn
            $ranked_data = Sale::selectRaw('agency_id, advertiser_id, employee_id, year, sum(gross_amount) as direct_sales')
                ->where('year', '=', $year)
                ->where('station', '=', $station)
                ->where('agency_id', '=', 1)
                ->orderBy('direct_sales', 'desc')
                ->groupBy(['agency_id', 'advertiser_id', 'employee_id', 'year'])
                ->get();

            $data = $ranked_data->groupBy('advertiser_id');

            foreach ($data as $key => $agency_sale) {
                $total_agency_sales = array_sum($agency_sale->pluck('direct_sales')->toArray());

                $ranked_sales['rank'] = $account_rank + 1;
                $ranked_sales['advertiser_id'] = $key;
                $ranked_sales['year'] = $year;
                $ranked_sales['sales'] = $total_agency_sales;

                foreach ($agency_sale as $sales) {
                    $executive = $sales->Employee->first_name.' '.$sales->Employee->last_name;

                    $ranked_sales['agency_name'] = $sales->Agency->name;
                    $ranked_sales['advertiser_name'] = $sales->Advertiser->name;
                    $ranked_sales['account_executive'] = $executive;
                }

                $ranked[] = $ranked_sales;

                $account_rank++;
            }
        }

        return response()->json([
            'ranked_data' => $ranked,
            'years' => $sale_year,
        ]);
    }

    public function getHomeByExecutives($station, $year)
    {
        $auth_user_id = Auth::id();

        $sales = Sale::with('Logs')
            ->selectRaw('month, year, sum(gross_amount) as gross_sales')
            ->where('station', $station)
            ->where('year', $year)
            ->orderBy('year')
            ->orderBy('month')
            ->groupBy('month', 'year')
            ->get();

        foreach ($sales as $sale) {
            $sale->month_name = $this->convertNumberToDate($sale->month);
        }

        // for manila chart data
        $months = $sales->pluck('month_name');
        $gross_sales = $sales->pluck('gross_sales')->toArray();

        // Account executives
        $account_executives = Sale::with('Logs')
            ->selectRaw('employee_id, month, sum(gross_amount) as ae_sales')
            ->where('station', '=', $station)
            ->where('year', '=', $year)
            ->where('employee_id', '=', $auth_user_id)
            ->groupBy('employee_id', 'month')
            ->get();

        $bar_ae = [];
        $bar_data = [];

        foreach ($account_executives as $employee) {
            $employee->executive_name = $employee->Employee->first_name.' '.$employee->Employee->last_name;

            if ($employee->Employee->color == null) {
                $employee->color = '#00c9ff';
            } else {
                $employee->color = $employee->Employee->color;
            }

            $bar_ae['executive_name'] = $this->convertNumberToDate($employee->month);
            $bar_ae['color'] = $this->generateRandomColor();
            $bar_ae['sales'] = $employee->ae_sales;

            $bar_data[] = $bar_ae;
        }

        // yearly sales
        $yearly_sales = Sale::with('Logs')
            ->selectRaw('year, sum(gross_amount) as yearly_sales')
            ->where('station', '=', $station)
            ->where('employee_id', '=', $auth_user_id)
            ->orderBy('year')
            ->groupBy('year')
            ->get();

        $chart_year = [];
        $chart_year_data = [];

        foreach ($yearly_sales as $yearly_sale) {
            $chart_year['years'] = $yearly_sale->year;
            $chart_year['color'] = $this->generateRandomColor();
            $chart_year['sales'] = $yearly_sale->yearly_sales;

            $chart_year_data[] = $chart_year;
        }

        // Account Executive Sales in Charts
        $monthlyExecutiveSales = Sale::with('Logs')
            ->selectRaw('month, year, sum(gross_amount) as monthly_ae_sales, employee_id')
            ->where('station', '=', $station)
            ->where('year', '=', $year)
            ->where('employee_id', '=', $auth_user_id)
            ->orderBy('year')
            ->orderBy('month')
            ->groupBy('month', 'year', 'employee_id')
            ->get();

        foreach ($monthlyExecutiveSales as $sales) {
            $sales->month_name = $this->convertNumberToDate($sales->month);

            $sales->executive_name = $sales->Employee->first_name.' '.$sales->Employee->last_name;

            if ($sales->Employee->color == null) {
                $sales->color = '#00c9ff';
            } else {
                $sales->color = $sales->Employee->color;
            }
        }

        $executiveNames = $monthlyExecutiveSales->groupBy('executive_name');

        $chart_data = [];

        // for Manila Sales
        $chart_ae['executive'] = $station === 'Manila' ? 'Manila' : ($station === 'Cebu' ? 'Cebu' : 'Davao').' Sales';
        $chart_ae['color'] = '#00c9ff';
        $chart_ae['sales'] = $gross_sales;

        $chart_data[] = $chart_ae;

        // for Executives
        foreach ($executiveNames as $executive => $sales) {
            $chart_ae['executive'] = $executive;
            $chart_ae['color'] = $sales->pluck('color')->first();
            $chart_ae['sales'] = $sales->pluck('monthly_ae_sales');

            $chart_data[] = $chart_ae;
        }

        // getting the top 10
        $ad_sales = [];
        $advertiser_data = [];

        $advertiser_sales = Sale::with('Advertiser', 'Employee')
            ->selectRaw('advertiser_id, employee_id, year, sum(gross_amount) as advertiser_sales')
            ->where('year', '=', $year)
            ->where('station', '=', $station)
            ->where('employee_id', '=', $auth_user_id)
            ->orderBy('advertiser_sales', 'desc')
            ->groupBy(['advertiser_id', 'employee_id', 'year'])
            ->get()
            ->take(10);

        $advertisers = $advertiser_sales->groupBy('advertiser_id');

        foreach ($advertisers as $key => $advertiser_sale) {
            $total_ad_sales = array_sum($advertiser_sale->pluck('advertiser_sales')->toArray());

            $ad_sales['id'] = $key;
            $ad_sales['year'] = $year;
            $ad_sales['sales'] = $total_ad_sales;

            foreach ($advertiser_sale as $sales) {
                $executive = $sales->Employee->first_name.' '.$sales->Employee->last_name;

                $ad_sales['name'] = $sales->Advertiser->name;
                $ad_sales['account_executive'] = $executive;
            }

            $advertiser_data[] = $ad_sales;
        }

        $ag_sales = [];
        $agency_data = [];

        $agency_sales = Sale::with('Agency', 'Employee')
            ->selectRaw('agency_id, employee_id, year, sum(gross_amount) as agency_sales')
            ->where('year', '=', $year)
            ->where('station', '=', $station)
            ->where('employee_id', '=', $auth_user_id)
            ->orderBy('agency_sales', 'desc')
            ->groupBy(['agency_id', 'employee_id', 'year'])
            ->get()
            ->take(10);

        $agency = $agency_sales->groupBy('agency_id');

        foreach ($agency as $key => $agency_sale) {
            $total_agency_sales = array_sum($agency_sale->pluck('agency_sales')->toArray());

            $ag_sales['id'] = $key;
            $ag_sales['year'] = $year;
            $ag_sales['sales'] = $total_agency_sales;

            foreach ($agency_sale as $sales) {
                $executive = $sales->Employee->first_name.' '.$sales->Employee->last_name;

                $ag_sales['name'] = $sales->Agency->name;
                $ag_sales['account_executive'] = $executive;
            }

            $agency_data[] = $ag_sales;
        }

        $direct_sales = [];
        $direct_agency_data = [];

        $direct_accounts = Sale::selectRaw('advertiser_id, agency_id, employee_id, year, sum(gross_amount) as direct_sales')
            ->where('station', '=', $station)
            ->where('year', '=', $year)
            ->where('agency_id', '=', 1)
            ->where('employee_id', '=', $auth_user_id)
            ->orderBy('direct_sales', 'desc')
            ->groupBy(['advertiser_id', 'agency_id', 'employee_id', 'year'])
            ->get()
            ->take(10);

        $direct = $direct_accounts->groupBy('advertiser_id');

        foreach ($direct as $key => $account) {
            $total_direct_sales = array_sum($account->pluck('direct_sales')->toArray());

            $direct_sales['advertiser_id'] = $key;
            $direct_sales['year'] = $year;
            $direct_sales['account_sale'] = $total_direct_sales;

            foreach ($account as $direct_account) {
                $direct_sales['advertiser_name'] = $direct_account->Advertiser->name;
                $direct_sales['account_executive'] = $direct_account->Employee->first_name.' '.$direct_account->Employee->last_name;
            }

            $direct_agency_data[] = $direct_sales;
        }

        $product_sales = [];
        $product_sale_data = [];

        $products = DB::table('sales')
            ->join('contracts', 'sales.contract_id', '=', 'contracts.id')
            ->join('employees', 'sales.employee_id', '=', 'employees.id')
            ->selectRaw("sales.contract_id, sales.agency_id, sales.advertiser_id,
            sum(sales.gross_amount) as product_sale, contracts.product as product_name, sales.employee_id,
            sales.year, concat(employees.first_name, ' ', employees.last_name) as account_executive")
            ->where('sales.year', '=', $year)
            ->where('sales.station', '=', $station)
            ->where('sales.employee_id', '=', $auth_user_id)
            ->groupBy(['sales.contract_id', 'sales.agency_id', 'sales.advertiser_id', 'sales.employee_id', 'sales.year'])
            ->get()
            ->take(10);

        $product = $products->groupBy('product_name');

        foreach ($product as $key => $product_data) {
            $total_product_sales = array_sum($product_data->pluck('product_sale')->toArray());

            $product_sales['product_name'] = $key;
            $product_sales['year'] = $year;
            $product_sales['product_sales'] = $total_product_sales;

            foreach ($product_data as $product_sale) {
                $product_sales['account_executive'] = $product_sale->account_executive;
            }

            $product_sale_data[] = $product_sales;
        }

        return response()->json([
            'months' => $months,
            'executive_sales' => $bar_data,
            'yearly_sales' => $chart_year_data,
            'monthly_executive_sales' => $chart_data,
            'advertisers' => $advertiser_data,
            'agency' => $agency_data,
            'direct' => $direct_agency_data,
            'product' => $product_sale_data,
            'report_previews' => $this->getDashboardReportPreviews($station),
            'responsibilities' => $this->getDescriptions(),
        ]);
    }

    private function getDashboardReportPreviews($station)
    {
        $current_year = (int) now()->format('Y');
        $current_month = now()->format('m');
        $current_quarter = (int) ceil(((int) $current_month) / 3);
        $quarter_months = $this->getQuarterMonths($current_quarter);

        $period_sales = function ($months) use ($station, $current_year) {
            return Sale::with([
                'Contract:id,number',
                'Employee:id,first_name,last_name',
            ])
                ->where('station', $station)
                ->where('year', $current_year)
                ->whereIn('month', $months)
                ->orderByDesc('month')
                ->orderByDesc('id')
                ->limit(5)
                ->get()
                ->map(function ($sale) {
                    $gross_amount = (float) $sale->gross_amount;
                    $invoice_amount = (float) $sale->invoice_amount;

                    return [
                        'id' => $sale->id,
                        'contract_number' => optional($sale->Contract)->number ?: 'N/A',
                        'account_executive' => trim(
                            optional($sale->Employee)->first_name.' '.
                            optional($sale->Employee)->last_name
                        ),
                        'gross_amount' => $gross_amount,
                        'invoice_amount' => $invoice_amount,
                        'adjustment' => round($gross_amount - $invoice_amount, 2),
                    ];
                });
        };

        $agency_sales = Sale::query()
            ->join('agencies', 'sales.agency_id', '=', 'agencies.id')
            ->selectRaw(
                'agencies.id, agencies.name, '
                .'SUM(sales.gross_amount) as gross_amount, '
                .'SUM(COALESCE(sales.invoice_amount, 0)) as invoice_amount, '
                .'SUM(sales.gross_amount) - SUM(COALESCE(sales.invoice_amount, 0)) as adjustment'
            )
            ->where('sales.station', $station)
            ->where('sales.year', $current_year)
            ->groupBy('agencies.id', 'agencies.name')
            ->orderByDesc('gross_amount')
            ->limit(5)
            ->get();

        $advertiser_sales = Sale::query()
            ->join('advertisers', 'sales.advertiser_id', '=', 'advertisers.id')
            ->selectRaw(
                'advertisers.id, advertisers.name, '
                .'SUM(sales.gross_amount) as gross_amount, '
                .'SUM(COALESCE(sales.invoice_amount, 0)) as invoice_amount, '
                .'SUM(sales.gross_amount) - SUM(COALESCE(sales.invoice_amount, 0)) as adjustment'
            )
            ->where('sales.station', $station)
            ->where('sales.year', $current_year)
            ->groupBy('advertisers.id', 'advertisers.name')
            ->orderByDesc('gross_amount')
            ->limit(5)
            ->get();

        return [
            'year' => $current_year,
            'month' => $this->convertNumberToDate($current_month),
            'quarter' => $current_quarter,
            'monthly' => $period_sales([$current_month]),
            'quarterly' => $period_sales($quarter_months),
            'agencies' => $agency_sales,
            'advertisers' => $advertiser_sales,
        ];
    }

    public function getRankedByExecutives($type, $station, $year)
    {
        $auth_user_id = Auth::id();

        $yearly_sales = Sale::selectRaw('year, employee_id, sum(gross_amount) as yearly_sales')
            ->where('station', '=', $station)
            ->where('employee_id', '=', $auth_user_id)
            ->orderBy('year', 'desc')
            ->groupBy(['year', 'employee_id'])
            ->get();

        $sale_year = [];

        $years = $yearly_sales->groupBy('year');

        foreach ($years as $key => $yearly_sale) {
            $sale_year[] = $key;
        }

        $advertiser_rank = 0;
        $ranked_sales = [];
        $ranked = [];

        // default that's being get is ranked advertiser data. "Top Advertisers"
        $ranked_data = Sale::selectRaw('advertiser_id, employee_id, year, sum(gross_amount) as advertiser_sales')
            ->where('year', '=', $year)
            ->where('station', '=', $station)
            ->where('employee_id', '=', $auth_user_id)
            ->orderBy('advertiser_sales', 'desc')
            ->groupBy(['advertiser_id', 'employee_id', 'year'])
            ->get();

        $data = $ranked_data->groupBy('advertiser_id');

        foreach ($data as $key => $advertiser_sale) {
            $total_agency_sales = array_sum($advertiser_sale->pluck('advertiser_sales')->toArray());

            $ranked_sales['rank'] = $advertiser_rank + 1;
            $ranked_sales['id'] = $key;
            $ranked_sales['year'] = $year;
            $ranked_sales['sales'] = $total_agency_sales;

            foreach ($advertiser_sale as $sales) {
                $executive = $sales->Employee->first_name.' '.$sales->Employee->last_name;

                $ranked_sales['name'] = $sales->Advertiser->name;
                $ranked_sales['account_executive'] = $executive;
            }

            $ranked[] = $ranked_sales;

            $advertiser_rank++;
        }

        if ($type == 'agency') {
            $agency_rank = 0;
            $ranked_sales = [];
            $ranked = [];

            // default that's being get is ranked agency data. "Top Agency"
            $ranked_data = Sale::selectRaw('agency_id, employee_id, year, sum(gross_amount) as agency_sales')
                ->where('year', '=', $year)
                ->where('station', '=', $station)
                ->where('employee_id', '=', $auth_user_id)
                ->orderBy('agency_sales', 'desc')
                ->groupBy(['agency_id', 'employee_id', 'year'])
                ->get();

            $data = $ranked_data->groupBy('agency_id');

            foreach ($data as $key => $agency_sale) {
                $total_agency_sales = array_sum($agency_sale->pluck('agency_sales')->toArray());

                $ranked_sales['rank'] = $agency_rank + 1;
                $ranked_sales['id'] = $key;
                $ranked_sales['year'] = $year;
                $ranked_sales['sales'] = $total_agency_sales;

                foreach ($agency_sale as $sales) {
                    $executive = $sales->Employee->first_name.' '.$sales->Employee->last_name;

                    $ranked_sales['name'] = $sales->Agency->name;
                    $ranked_sales['account_executive'] = $executive;
                }

                $ranked[] = $ranked_sales;

                $agency_rank++;
            }
        }

        if ($type == 'direct') {
            $account_rank = 0;
            $ranked_sales = [];
            $ranked = [];

            // default that's being get is ranked agency data. "Top Agency"nn
            $ranked_data = Sale::selectRaw('agency_id, advertiser_id, employee_id, year, sum(gross_amount) as direct_sales')
                ->where('year', '=', $year)
                ->where('station', '=', $station)
                ->where('agency_id', '=', 1)
                ->where('employee_id', '=', $auth_user_id)
                ->orderBy('direct_sales', 'desc')
                ->groupBy(['agency_id', 'advertiser_id', 'employee_id', 'year'])
                ->get();

            $data = $ranked_data->groupBy('advertiser_id');

            foreach ($data as $key => $agency_sale) {
                $total_agency_sales = array_sum($agency_sale->pluck('direct_sales')->toArray());

                $ranked_sales['rank'] = $account_rank + 1;
                $ranked_sales['advertiser_id'] = $key;
                $ranked_sales['year'] = $year;
                $ranked_sales['sales'] = $total_agency_sales;

                foreach ($agency_sale as $sales) {
                    $executive = $sales->Employee->first_name.' '.$sales->Employee->last_name;

                    $ranked_sales['agency_name'] = $sales->Agency->name;
                    $ranked_sales['advertiser_name'] = $sales->Advertiser->name;
                    $ranked_sales['account_executive'] = $executive;
                }

                $ranked[] = $ranked_sales;

                $account_rank++;
            }
        }

        return response()->json([
            'ranked_data' => $ranked,
            'years' => $sale_year,
        ]);
    }

    public function getDescriptions()
    {
        $job_id = Auth::user()->Job->id;

        return Description::with('Job')
            ->where('job_id', '=', $job_id)
            ->get();
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Traits\GeneratorTrait;
use App\Http\Traits\LogTrait;
use App\Http\Traits\ProcessorTrait;
use App\Http\Traits\SystemDefaultsTrait;
use App\Models\Advertiser;
use App\Models\Agency;
use App\Models\Contract;
use App\Models\ContractRevision;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{
    use GeneratorTrait;
    use LogTrait;
    use ProcessorTrait;
    use SystemDefaultsTrait;

    public function index(Request $request)
    {
        $executive = Auth::id();
        $user_level = Auth::user()->Job->level;

        $executives = Employee::with('Logs')
            ->where('is_active', 1)
            ->whereHas('Job', function ($query) {
                $query->where('level', '!=', '3');
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        foreach ($executives as $employee) {
            $employee->full_name = $employee->first_name.' '.$employee->last_name;
        }

        // get the latest contract year.
        $latest_contract = Contract::with('Agency', 'Advertiser', 'Employee')
            ->latest()
            ->first();

        $latest_year = date('Y', strtotime($latest_contract->created_at));

        $filter = $request['filter'];
        $contract_type = $request['contract_type'] ? $request['contract_type'] : false;

        $contract_years = Contract::with('Agency', 'Advertiser', 'Employee')
            ->where('bo_type', '=', 'normal')
            ->where('is_active', '=', '1')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($contract_years as $year) {
            $year->year = date('Y', strtotime($year->created_at));
        }

        $years = $contract_years->groupBy('year');

        if ($user_level === '2') {
            // filtering the datatables by year & executive
            if ($filter) {
                $year = ! $request['year'] ? $latest_year : $request['year'];

                $contracts = $this->getContractsByExecutives($executive, $year, $contract_type);

                return response()->json([
                    'contracts' => $this->processContract($contracts),
                    'years' => $years,
                ]);
            }

            $contracts = $this->getContractsByExecutives($executive, $latest_year, $contract_type);
        } else {
            // filtering the datatables by year.
            if ($filter) {
                $year = ! $request['year'] ? $latest_year : $request['year'];

                $contracts = $this->getContracts($year, $contract_type);

                return response()->json([
                    'contracts' => $this->processContract($contracts),
                    'years' => $years,
                    'executives' => $executives,
                ]);
            }

            $contracts = $this->getContracts($latest_year, $contract_type);
        }

        $parents = Contract::with('Logs')
            ->where('bo_type', '=', 'normal')
            ->orderBy('bo_number')
            ->get()
            ->pluck('bo_number');

        return response()->json([
            'contracts' => $this->processContract($contracts),
            'parent_bos' => $parents,
            'years' => $years,
            'executives' => $executives,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'station' => ['required', 'array'],
            'agency_id' => 'required',
            'advertiser_id' => 'required',
            'product' => 'required',
            'bo_type' => 'required',
            'bo_number' => 'required',
            'commencement' => 'required',
            'end_of_broadcast' => 'required',
            'detail' => 'required',
            'manila_cash' => 'required',
            'cebu_cash' => 'required',
            'davao_cash' => 'required',
            'total_cash' => 'required',
            'manila_ex' => 'required',
            'cebu_ex' => 'required',
            'davao_ex' => 'required',
            'total_ex' => 'required',
            'total_amount' => 'required',
            'manila_prod' => 'required',
            'cebu_prod' => 'required',
            'davao_prod' => 'required',
            'total_prod' => 'required',
        ]);

        if ($request['bo_type'] == 'normal') {
            $request['parent_bo'] = 'none';
        }

        $employee_id = Auth::id();
        $firstName = Auth::user()->first_name;
        $middleName = Auth::user()->middle_name;
        $lastName = Auth::user()->last_name;

        if ($validator->passes()) {
            // creation of contract number
            if (! $middleName) {
                $contract_number = date('Ymd').'-'.$firstName[0].$lastName[0].'-'.mt_rand(100000, 999999);
            } else {
                $contract_number = date('Ymd').'-'.$firstName[0].$middleName[0].$lastName[0].'-'.mt_rand(100000, 999999);
            }

            // storing the stations array as a string
            $stations = implode($request['station']);

            $request['station'] = $stations;
            $request['number'] = $contract_number;
            $request['employee_id'] = Auth::id();
            $request['is_printed'] = 0;
            $request['is_active'] = 1;

            $contract = new Contract($request->all());
            $contract->save();

            $new_contract = Contract::with('Logs')
                ->latest()
                ->get()
                ->first();

            $this->Log(
                'Added a new contract with a contract number of '.$new_contract['number'].' and a Gross Amount of '.$new_contract['total_amount'],
                $this->authenticatedUser('id'),
                'contracts',
                $new_contract['id']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'A contract has been successfully added!',
            ], 201);
        }

        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()->all(),
        ], 400);
    }

    public function show($id)
    {
        $contract = Contract::with('Sale.Contract', 'Sale.Revision', 'Employee', 'Advertiser', 'Agency')->findOrFail($id);

        foreach ($contract->Sale as $sale) {
            $sale->month_name = $this->convertNumberToDate($sale->month);
            $sale->sale_type = $this->translateSaleType($sale->type);

            // getting the breakdown amount
            // 07/11/2023 - Always add total prod in breakdown amount as per Michelle Buna.
            // $contract->breakdown_amount = array_sum($sale->where('contract_id', $contract->id)->where('type', '!=', 'totalprod')->pluck('gross_amount')->toArray());
            $contract->breakdown_amount = array_sum($contract->Sale->pluck('gross_amount')->toArray());

            // getting the total amount of invoice
            $contract->invoice_amount = array_sum($sale->Contract->Sale->pluck('invoice_amount')->toArray()); // $sale->invoice_amount;

            // if the breakdown amount is not equal to the invoice amount it will fall to discrepancy
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
                    $sale->invoice_info = 'discrepancy';
                } else {
                    $sale->invoice_info = 'billed';
                }
            } elseif ($sale->invoice_no == '') {
                $sale->invoice_info = 'unbilled';
            }
        }

        $executives = Employee::with('Logs')
            ->where('is_active', 1)
            ->whereHas('Job', function ($query) {
                $query->where('level', '!=', '3');
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        foreach ($executives as $executive) {
            $executive->full_name = $executive->first_name.' '.$executive->last_name;
        }

        $agencies = Agency::with('Logs')
            ->where('is_active', 1)
            ->where('id', '!=', $contract['id'])
            ->get();

        $advertisers = Advertiser::with('Logs')
            ->where('is_active', 1)
            ->where('id', '!=', $contract['id'])
            ->get();

        $parents = Contract::with('Logs')
            ->where('bo_number', '!=', $contract['bo_number'])
            ->where('bo_type', '=', 'normal')
            ->orderBy('bo_number')
            ->get()
            ->pluck('bo_number');

        $contract['station'] = $this->getStations($contract['station']);

        return response()->json([
            'contract' => $contract,
            'executives' => $executives,
            'agencies' => $agencies,
            'advertisers' => $advertisers,
            'parent_bos' => $parents,
        ]);
    }

    public function update($id, Request $request)
    {
        $contract = Contract::with('Logs')->findOrFail($id);

        $request['station'] = implode(' ', $request['station']);

        // check if revision exists
        $revision = ContractRevision::with('Contract')
            ->where('contract_id', '=', $id)
            ->get();

        if ($contract['advertiser_id'] === 0) {
            $contract['advertiser_id'] = $request['advertiser_id'];
        }

        if ($contract['agency_id'] === 0) {
            $contract['agency_id'] = $request['agency_id'];
        }

        if ($revision->count() == 1) {
            $contract_revision = new ContractRevision([
                'contract_id' => $contract['id'],
                'number' => $contract['number'],
                'station' => $contract['station'],
                'agency_id' => $contract['agency_id'],
                'advertiser_id' => $contract['advertiser_id'],
                'product' => $contract['product'],
                'bo_type' => $contract['bo_type'],
                'parent_bo' => $contract['parent_bo'],
                'bo_number' => $contract['bo_number'],
                'ce_number' => $contract['ce_number'],
                'bo_date' => $contract['bo_date'],
                'commencement' => $contract['commencement'],
                'end_of_broadcast' => $contract['end_of_broadcast'],
                'detail' => $contract['detail'],
                'package_cost' => $contract['package_cost'],
                'package_cost_vat' => $contract['package_cost_vat'],
                'package_cost_salesdc' => $contract['package_cost_salesdc'],
                'manila_cash' => $contract['manila_cash'],
                'cebu_cash' => $contract['cebu_cash'],
                'davao_cash' => $contract['davao_cash'],
                'total_cash' => $contract['total_cash'],
                'manila_ex' => $contract['manila_ex'],
                'cebu_ex' => $contract['cebu_ex'],
                'davao_ex' => $contract['davao_ex'],
                'total_ex' => $contract['total_ex'],
                'total_amount' => $contract['total_amount'],
                'prod_cost' => $contract['prod_cost'],
                'prod_cost_vat' => $contract['prod_cost_vat'],
                'prod_cost_salesdc' => $contract['prod_cost_salesdc'],
                'manila_prod' => $contract['manila_prod'],
                'cebu_prod' => $contract['cebu_prod'],
                'davao_prod' => $contract['davao_prod'],
                'total_prod' => $contract['total_prod'],
                'employee_id' => $contract['employee_id'],
                'is_printed' => $contract['is_printed'],
                'is_active' => $contract['is_active'],
                'version' => $revision->first()->version + 1,
            ]);

            $contract_revision->save();
        } else {
            $contract_revision = new ContractRevision([
                'contract_id' => $contract['id'],
                'number' => $contract['number'],
                'station' => $contract['station'],
                'agency_id' => $contract['agency_id'],
                'advertiser_id' => $contract['advertiser_id'],
                'product' => $contract['product'],
                'bo_type' => $contract['bo_type'],
                'parent_bo' => $contract['parent_bo'],
                'bo_number' => $contract['bo_number'],
                'ce_number' => $contract['ce_number'],
                'bo_date' => $contract['bo_date'],
                'commencement' => $contract['commencement'],
                'end_of_broadcast' => $contract['end_of_broadcast'],
                'detail' => $contract['detail'],
                'package_cost' => $contract['package_cost'],
                'package_cost_vat' => $contract['package_cost_vat'],
                'package_cost_salesdc' => $contract['package_cost_salesdc'],
                'manila_cash' => $contract['manila_cash'],
                'cebu_cash' => $contract['cebu_cash'],
                'davao_cash' => $contract['davao_cash'],
                'total_cash' => $contract['total_cash'],
                'manila_ex' => $contract['manila_ex'],
                'cebu_ex' => $contract['cebu_ex'],
                'davao_ex' => $contract['davao_ex'],
                'total_ex' => $contract['total_ex'],
                'total_amount' => $contract['total_amount'],
                'prod_cost' => $contract['prod_cost'],
                'prod_cost_vat' => $contract['prod_cost_vat'],
                'prod_cost_salesdc' => $contract['prod_cost_salesdc'],
                'manila_prod' => $contract['manila_prod'],
                'cebu_prod' => $contract['cebu_prod'],
                'davao_prod' => $contract['davao_prod'],
                'total_prod' => $contract['total_prod'],
                'employee_id' => $contract['employee_id'],
                'is_printed' => $contract['is_printed'],
                'is_active' => $contract['is_active'],
                'version' => 1,
            ]);

            $contract_revision->save();
        }

        $contract->update($request->all());

        $this->Log(
            'Updated a contract with a contract number of '.$contract['number'].'. Check revisions.',
            $this->authenticatedUser('id'),
            'contracts',
            $contract['id']
        );

        $contract['station'] = $this->getStations($contract['station']);

        return response()->json([
            'status' => 200,
            'message' => 'Contract '.$contract['number'].' has been updated!',
        ]);
    }

    public function destroy($id)
    {
        // No deletion, company policy
    }

    public function setContractStatus($id, Request $request)
    {
        $contract = Contract::with('Logs')->findOrFail($id);

        $contract->is_active = $request['status'];
        $contract->save();

        if ($contract->is_active === '1') {
            $this->Log(
                'Activated a contract',
                $this->authenticatedUser('id'),
                'contracts',
                $contract['id']
            );

            return response()->json(['status' => 'success', 'message' => 'A contract has been activated!']);
        }

        $this->Log(
            'Deactivated a contract',
            $this->authenticatedUser('id'),
            'contracts',
            $contract['id']
        );

        return response()->json(['status' => 'success', 'message' => 'A contract has been deactivated!']);
    }

    public function generatePDF($id)
    {
        $contract = Contract::with('Agency', 'Advertiser', 'Employee')->findOrFail($id);

        view()->share('contract', $contract);
        $pdf = Pdf::loadView('layouts.print', $contract->toArray());

        $contract['is_printed'] = 1;

        $contract->save();

        $this->Log(
            'Generated PDF for contract number.',
            $this->authenticatedUser('id'),
            'contracts',
            $contract['id']
        );

        return $pdf->download(date('Y-m-d').'-'.$contract['number'].'-'.date('YmdHi').'-forPDFPrinting.pdf');
    }

    public function generateText($id)
    {
        $contract = Contract::with('Agency', 'Advertiser', 'Employee')->findOrFail($id);

        $contract['is_printed'] = 1;
        $contract->save();

        $file_name = $contract['number'].'-'.date('Ymd-Hi').'-forTextPrinting.txt';

        if (! $contract->Employee->middle_name) {
            $contract['ae'] = $contract->Employee->first_name[0].$contract->Employee->last_name[0];
        } else {
            $contract['ae'] = $contract->Employee->first_name[0].$contract->Employee->middle_name[0].$contract->Employee->last_name[0];
        }

        $my_file = fopen($file_name, 'w') or exit('Unable to open file');

        // 20240226 Update; Removed the verification if the contract is from Cebu and Davao
        // $verify = $this->checkLocal($contract['number']);
        $txt = $this->getRawText($contract);

        fwrite($my_file, $txt);
        fclose($my_file);

        // Force download the text file to user's download file
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($file_name));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: '.filesize($file_name));
        readfile($file_name);

        $this->Log(
            'Generated text file for contract',
            $this->authenticatedUser('id'),
            'contracts',
            $contract['id']
        );

        exit;
    }

    public function getExecutive($id)
    {
        $executive = Employee::with('Logs')->findOrFail($id);

        $name = $executive->first_name.' '.$executive->last_name;

        return response()->json([
            'account_executive' => $name,
        ]);
    }

    public function getContractSales(Request $request)
    {
        $contract_id = $request['contract_id'];

        $executive = Auth::id();
        $user_level = Auth::user()->Job->level;

        // get the latest contract year.
        $latest_contract = Contract::has('Sale')
            ->with('Agency', 'Advertiser', 'Employee')
            ->latest()
            ->first();

        $latest_year = date('Y', strtotime($latest_contract->created_at));

        $filter = $request['contract_sales'] ? $request['contract_sales'] : false;
        $contract_type = $request['contract_type'] ? $request['contract_type'] : false;

        $year = $request['year'] ? $request['year'] : $latest_year;

        $contract_years = Contract::has('Sale')
            ->with('Agency', 'Advertiser', 'Employee')
            ->where('is_active', '=', '1')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($filter) {
            $sales = $this->getContractsByExecutives($executive, $year, $contract_type);
        } else {
            $sales = $this->getContracts($year, $contract_type);
        }

        $sales = $this->processContractSale($sales, $filter, $user_level);

        return response()->json([
            'sales' => $sales,
            'current_year' => $year,
            'years' => $contract_years,
        ]);
    }
}

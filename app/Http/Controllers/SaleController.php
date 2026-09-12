<?php

namespace App\Http\Controllers;

use App\Http\Traits\LogTrait;
use App\Http\Traits\ProcessorTrait;
use App\Http\Traits\SystemDefaultsTrait;
use App\Models\Contract;
use App\Models\Sale;
use App\Models\SaleLog;
use App\Models\SaleRevision;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SaleController extends Controller
{
    use LogTrait;
    use ProcessorTrait;
    use SystemDefaultsTrait;

    public function index(Request $request)
    {
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

        $contract_years = Contract::has('Sale')
            ->with('Agency', 'Advertiser', 'Employee')
            ->where('is_active', '=', '1')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($contract_years as $year) {
            $year->year = date('Y', strtotime($year->created_at));
        }

        $years = $contract_years->groupBy('year');

        $year = $request['year'] ? $request['year'] : $latest_year;

        // getting the sales by user level
        if ($user_level === '2') {
            if ($filter) {
                $contracts = $this->getSalesByExecutives($executive, $year, $contract_type);

                return response()->json([
                    'contract' => $this->processContractSale($contracts, $filter, $user_level),
                    'years' => $years,
                ]);
            }

            $contracts = $this->getSalesByExecutives($executive, $latest_year, $contract_type);
        } else {
            if ($filter) {
                $contracts = $this->getSales($year, $contract_type);

                return response()->json([
                    'contract' => $this->processContractSale($contracts, $filter, $user_level),
                    'years' => $years,
                ]);
            }

            $contracts = $this->getSales($latest_year, $contract_type);
        }

        return response()->json([
            'contract' => $this->processContractSale($contracts, $filter, $user_level),
            'years' => $years,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'station' => 'required',
            'month' => 'required',
            'year' => 'required',
            'type' => 'required',
            'transaction' => 'required',
            'amount' => 'required',
            'gross_amount' => 'required',
        ]);

        if ($validator->passes()) {
            $sales = new Sale($request->all());

            $sales->save();

            $new_sales = Sale::with('Logs')
                ->latest()
                ->get()
                ->first();

            $this->Log(
                'Sales breakdown added to Contract ID #'.$new_sales['contract_id'].' with a gross amount of '.$new_sales['gross_amount'],
                $this->authenticatedUser('id'),
                'sales',
                $new_sales['id']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'A new sales breakdown has been added!',
            ], 201);
        }

        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()->all(),
        ], 400);
    }

    public function show($id)
    {
        $sale = Sale::with('Agency',
            'Advertiser',
            'Employee',
            'Logs',
            'Revision.Sale',
            'Revision.Contract')
            ->findOrFail($id);

        $sale['month_name'] = $this->convertNumberToDate($sale['month']);
        $sale['sale_type'] = $this->translateSaleType($sale['type']);

        foreach ($sale->Revision as $revision) {
            $revision['month_name'] = $this->convertNumberToDate($revision['month']);
            $revision['sale_type'] = $this->translateSaleType($revision['type']);
        }

        return response()->json(['sale' => $sale]);
    }

    public function update($id, Request $request)
    {
        $sale = Sale::with('Logs')->findOrFail($id);

        $employee_id = Auth::id();

        $validator = Validator::make($request->all(), [
            'month' => 'required',
            'year' => 'required',
            'type' => 'required',
            'transaction' => 'required',
            'amount' => 'required',
            'gross_amount' => 'required',
        ]);

        if ($validator->passes()) {
            // verify if the sales_id is revised
            $revision = SaleRevision::with('Sales', 'Contract')
                ->latest()
                ->where('sales_id', $sale->id)
                ->get();

            if ($request['invoice_no'] != $sale['invoice_no']) {
                $invoice_validator = Validator::make($request->all(), [
                    'invoice_no' => 'required',
                    'invoice_date' => 'required',
                ]);

                if ($invoice_validator->passes()) {
                    $sale->update($request->all());

                    $this->Log(
                        'Changed the invoice_no of the sales breakdown with a bo_number of '.$sale['bo_number'].' and a contract number of '.$sale->Contract->number.' from '.$sale['invoice_no'].' to '.$request['invoice_no'],
                        $this->authenticatedUser('id'),
                        'sales',
                        $sale['id']
                    );

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Sale has been billed!',
                    ], 201);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => $invoice_validator->errors()->all(),
                ], 400);
            } else {
                if ($request['month'] != $sale['month']) {
                    $this->Log(
                        'Changed the month of the sales breakdown with a bo_number of '.$sale['bo_number'].' and a contract number of '.$sale->Contract->number.' from '.$sale['month'].' to '.$request['month'],
                        $this->authenticatedUser('id'),
                        'sales',
                        $sale['id']
                    );
                }

                if ($request['year'] != $sale['year']) {
                    $this->Log(
                        'Changed the year of the sales breakdown with a bo_number of '.$sale['bo_number'].' and a contract number of '.$sale->Contract->number.' from '.$sale['year'].' to '.$request['year'],
                        $this->authenticatedUser('id'),
                        'sales',
                        $sale['id']
                    );
                }

                if ($request['type'] != $sale['type']) {
                    $this->Log(
                        'Changed the type of the sales breakdown from '.$sale['type'].' to '.$request['type'],
                        $this->authenticatedUser('id'),
                        'sales',
                        $sale['id']
                    );
                }

                if ($request['transaction'] != $sale['transaction']) {
                    $this->Log(
                        'Changed the type of amount of the sales breakdown from '.$sale['transaction'].' to '.$request['amount_type'],
                        $this->authenticatedUser('id'),
                        'sales',
                        $sale['id']
                    );
                }

                if ($request['amount'] != $sale['amount']) {
                    $this->Log(
                        'Changed the amount of the sales breakdown with a bo_number of '.$sale['bo_number'].' and a contract number of '.$sale->Contract->number.' and the amount from '.$sale['amount'].' to '.$request['amount'],
                        $this->authenticatedUser('id'),
                        'sales',
                        $sale['id']
                    );
                }

                if ($request['gross_amount'] != $sale['gross_amount']) {
                    $this->Log(
                        'Changed the gross amount of the sales breakdown with a bo_number of '.$sale['bo_number'].' and a contract number of '.$sale->Contract->number.' and the amount from '.$sale['gross_amount'].' to '.$request['gross_amount'],
                        $this->authenticatedUser('id'),
                        'sales',
                        $sale['id']
                    );
                }

                if ($revision->count() > 0) {
                    $sales_revision = new SaleRevision([
                        'sales_id' => $sale['id'],
                        'contract_id' => $sale['contract_id'],
                        'station' => $sale['station'],
                        'month' => $sale['month'],
                        'year' => $sale['year'],
                        'type' => $sale['type'],
                        'transaction' => $sale['transaction'],
                        'amount' => $sale['amount'],
                        'gross_amount' => $sale['gross_amount'],
                        'invoice_no' => $sale['invoice_no'],
                        'version' => $revision->first()->version + 1,
                        'note' => $request['note'],
                    ]);

                } else {
                    $sales_revision = new SaleRevision([
                        'sales_id' => $sale['id'],
                        'contract_id' => $sale['contract_id'],
                        'station' => $sale['station'],
                        'month' => $sale['month'],
                        'year' => $sale['year'],
                        'type' => $sale['type'],
                        'transaction' => $sale['transaction'],
                        'amount' => $sale['amount'],
                        'gross_amount' => $sale['gross_amount'],
                        'invoice_no' => $sale['invoice_no'],
                        'version' => 1,
                        'note' => $request['note'],
                    ]);

                }

                $sales_revision->save();
            }

            $sale->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Sales breakdown has been updated!',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()->all(),
        ], 400);
    }

    public function destroy($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'reason' => 'required',
        ]);

        if ($validator->passes()) {
            $password = $request['password'];
            $password_verification = Hash::check($password, $this->password());
            $user_id = Auth::id();

            if (! $password_verification) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Incorrect password!',
                ], 400);
            } else {
                $sale = Sale::with('Logs')->findOrFail($id);

                $log = new SaleLog([
                    'sales_id' => $sale->id,
                    'action' => 'Removed sale from contract '.$sale->Contract->number.', bo number of '.$sale->Contract->bo_number.' with the amount of '.$sale->amount.' and gross amount of '.$sale->gross_amount.'. reason: '.$request['reason'],
                    'employee_id' => $user_id,
                ]);

                $log->save();

                $sale->delete();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Sale has been deleted!',
                ]);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()->all(),
        ], 400);
    }

    public function save_invoice($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'requestType' => 'required',
            'invoice_no' => 'required|min:4',
            'invoice_date' => 'required|date',
        ]);

        if ($validator->passes()) {
            $sale = Sale::with('Logs')->findOrFail($id);

            $type = $request['requestType'];

            // Add/Update invoice number
            $sale['invoice_amount'] = $request['invoice_amount'];
            $sale['invoice_no'] = $request['invoice_no'];
            $sale['invoice_date'] = $request['invoice_date'];

            $sale->save();

            $this->Log(
                $type == 'add' ? 'Added' : 'Updated the'.' invoice number to sale id: #'.$sale->id.' contract number: #'.$sale->Contract->number.'. Reason: '.$request['update_reason'],
                $this->authenticatedUser('id'),
                'sales',
                $sale['id']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice has been successfully saved!',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()->all(),
        ], 400);
    }

    public function get_revision($id, Request $request)
    {
        $revision = SaleRevision::with('Sale.Contract', 'Sale.Logs')->findOrFail($id);

        return response()->json([
            'revision' => $revision,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Traits\LogTrait;
use App\Http\Traits\SystemDefaultsTrait;
use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgencyController extends Controller
{
    use LogTrait;
    use SystemDefaultsTrait;

    public function index()
    {
        $agencies = Agency::with('Logs')
            ->orderBy('name')
            ->get();

        return response()->json([
            'agencies' => $agencies,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'contact_number' => 'required',
            'address' => 'required',
            'is_accredited' => 'required',
        ]);

        if ($validator->passes()) {
            $request['is_active'] = 1;

            $agency = new Agency($request->all());

            $agency->save();

            $new_agency = Agency::with('Logs')
                ->latest()
                ->get()
                ->first();

            $this->Log(
                'Added a new agency named '.$new_agency['name'],
                $this->authenticatedUser('id'),
                'agencies',
                $new_agency['id']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'An agency has been created!',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $validator->errors(),
        ]);
    }

    public function show($id)
    {
        $agency = Agency::with('Logs')->findOrFail($id);

        return response()->json([
            'agency' => $agency,
        ]);
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'contact_number' => 'required',
            'address' => 'required',
            'is_accredited' => 'required',
        ]);

        if ($validator->passes()) {
            $agency = Agency::with('Logs')->findOrFail($id);

            if ($agency['name'] != $request['name']) {
                $this->Log(
                    'Updated the agency name from '.$agency['name'].' to '.$request['name'],
                    $this->authenticatedUser('id'),
                    'agencies',
                    $agency['id']
                );
            }

            if ($agency['contact_number'] != $request['contact_number']) {
                $this->Log(
                    'Updated the agency contact number from '.$agency['contact_number'].' to '.$request['contact_number'],
                    $this->authenticatedUser('id'),
                    'agencies',
                    $agency['id']
                );
            }

            if ($agency['address'] != $request['address']) {
                $this->Log(
                    'Updated the agency address from '.$agency['address'].' to '.$request['address'],
                    $this->authenticatedUser('id'),
                    'agencies',
                    $agency['id']
                );
            }

            if ($agency['is_accredited'] != $request['is_accredited']) {
                if ($request['is_accredited'] === '0') {
                    $this->Log(
                        'Set the kbp accreditation of '.$agency['name'].' to unaccredited',
                        $this->authenticatedUser('id'),
                        'agencies',
                        $agency['id']
                    );
                }

                if ($request['kbp_accredited'] === '1') {
                    $this->Log(
                        'Set the kbp accreditation of '.$agency['name'].' to accredited',
                        $this->authenticatedUser('id'),
                        'agencies',
                        $agency['id']
                    );
                }
            }

            if ($agency['is_active'] != $request['is_active']) {
                if ($request['is_active'] === '0') {
                    $this->Log(
                        'Set the status of '.$agency['name'].' to inactive',
                        $this->authenticatedUser('id'),
                        'agencies',
                        $agency['id']
                    );
                }

                if ($request['is_active'] === '1') {
                    $this->Log(
                        'Set the status of '.$agency['name'].' to active',
                        $this->authenticatedUser('id'),
                        'agencies',
                        $agency['id']
                    );
                }
            }

            $agency->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'The agency has been updated!',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $validator->errors(),
        ], 400);
    }

    public function destroy($id)
    {
        // No data deletion, company policy
    }

    public function checkName(Request $request)
    {
        $agencyName = $request['name'];

        $agencyCount = Agency::with('Logs')
            ->where('name', '=', $agencyName)
            ->get()
            ->count();

        return response()->json([
            'status' => 'success',
            'matches' => $agencyCount,
        ]);
    }
}

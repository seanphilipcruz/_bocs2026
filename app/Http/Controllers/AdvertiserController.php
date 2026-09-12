<?php

namespace App\Http\Controllers;

use App\Http\Traits\LogTrait;
use App\Http\Traits\SystemDefaultsTrait;
use App\Models\Advertiser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdvertiserController extends Controller
{
    use LogTrait;
    use SystemDefaultsTrait;

    public function index()
    {
        $advertisers = Advertiser::with('Logs')
            ->orderBy('name')
            ->get();

        return response()->json([
            'advertisers' => $advertisers,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->passes()) {
            $request['is_active'] = 1;

            $advertiser = new Advertiser($request->all());
            $advertiser->save();

            $new_advertiser = Advertiser::with('Logs')
                ->latest()
                ->get()
                ->first();

            $this->Log(
                'Added a new advertiser named '.$new_advertiser['name'],
                $this->authenticatedUser('id'),
                'advertisers',
                $new_advertiser['id']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'An advertiser has been created!',
            ], 201);
        }

        return response()->json([
            'status' => 'error',
            'message' => $validator->errors(),
        ], 400);
    }

    public function show($id)
    {
        $advertiser = Advertiser::with('Logs')->findOrFail($id);

        return response()->json([
            'advertiser' => $advertiser,
        ]);
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->passes()) {
            $advertiser = Advertiser::with('Logs')->findOrFail($id);

            if ($advertiser['name'] != $request['name']) {
                $this->Log(
                    'Updated the advertiser name from '.$advertiser['name'].' to '.$request['name'],
                    $this->authenticatedUser('id'),
                    'advertisers',
                    $advertiser['id']
                );
            }

            if ($advertiser['is_active'] != $request['is_active']) {
                if ($request['is_active'] === '0') {
                    $this->Log(
                        'Set the status of '.$advertiser['name'].' to inactive',
                        $this->authenticatedUser('id'),
                        'advertisers',
                        $advertiser['id']
                    );
                }

                if ($request['is_active'] === '1') {
                    $this->Log(
                        'Set the status of '.$advertiser['name'].' to active',
                        $this->authenticatedUser('id'),
                        'advertisers',
                        $advertiser['id']
                    );
                }
            }

            $advertiser->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'The advertiser has been updated!',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $validator->errors(),
        ], 400);
    }

    public function destroy($id)
    {
        // No deletion of data. company policy
    }

    public function checkName(Request $request)
    {
        $advertiserName = $request['name'];

        $advertiserCount = Advertiser::with('Logs')
            ->where('name', '=', $advertiserName)
            ->get()
            ->count();

        return response()->json([
            'status' => 'success',
            'matches' => $advertiserCount,
        ]);
    }
}

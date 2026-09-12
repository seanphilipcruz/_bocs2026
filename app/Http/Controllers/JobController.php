<?php

namespace App\Http\Controllers;

use App\Http\Traits\LogTrait;
use App\Http\Traits\SystemDefaultsTrait;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{
    use LogTrait;
    use SystemDefaultsTrait;

    public function index(Request $request)
    {
        $jobs = Job::with('Logs')
            ->get();

        return response()->json([
            'jobs' => $jobs,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'level' => 'required',
        ]);

        $employee = Auth::user();

        if ($validator->passes()) {
            $request['is_active'] = 1;

            $job = new Job($request->all());

            $job->save();

            $new_job = Job::with('Logs')
                ->latest()
                ->first();

            $this->Log(
                'Created a new job description named '.$new_job->title,
                $this->authenticatedUser('id'),
            );

            return response()->json([
                'status' => 'success',
                'message' => 'A new job description has been created!',
            ], 201);
        }

        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()->all(),
        ], 400);
    }

    public function show($id)
    {
        $job = Job::with('Logs')->findOrFail($id);

        return response()->json([
            'job' => $job,
        ]);
    }

    public function update($id, Request $request)
    {
        $job = Job::with('Logs')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'level' => 'required',
        ]);

        if ($validator->passes()) {
            if ($job['title'] !== $request['title']) {
                $this->Log(
                    'Updated '.$job['title'].' from '.$job['title'].' to '.$request['title'].'.',
                    $this->authenticatedUser('id')
                );
            }

            if ($job['level'] !== $request['level']) {
                $this->Log(
                    'Updated the level of '.$job['title'].' from '.$job['level'].' to '.$request['level'].'.',
                    $this->authenticatedUser('id')
                );
            }

            $job->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Job information has been updated!',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()->all(),
        ], 400);
    }

    public function destroy($id)
    {
        // Obsolete, company policy
    }
}

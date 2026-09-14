<?php

namespace App\Http\Controllers;

use App\Http\Traits\LogTrait;
use App\Http\Traits\ProcessorTrait;
use App\Http\Traits\SystemDefaultsTrait;
use App\Models\Employee;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    use LogTrait;
    use ProcessorTrait;
    use SystemDefaultsTrait;

    public function index(Request $request)
    {
        $employees = Employee::with('Job', 'Logs')->get();

        return response()->json([
            'employees' => $employees,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'last_name' => 'required',
            'first_name' => 'required',
            'birthday' => 'required',
            'email' => 'required',
            'job_id' => 'required',
        ]);

        if ($validator->passes()) {
            if ($request['nickname'] === null || $request['nickname'] === '') {
                $request['nickname'] = $request['first_name'];
            }

            $request['is_active'] = 1;

            $request['password'] = Hash::make(date('mdy', strtotime($request['birthday'])));

            $employee = new Employee($request->all());
            $employee->save();

            $latest_employee = Employee::with('Logs')
                ->latest()
                ->get()
                ->first();

            $this->Log('Added an Employee named '.$latest_employee->first_name.' '.$latest_employee->last_name.'.', $latest_employee->id);

            return response()->json([
                'status' => 'success',
                'message' => 'An employee has been created!',
            ], 201);
        }

        return $this->validationError($validator);
    }

    public function show($id)
    {
        $employee = Employee::with('Job', 'Logs')->findOrFail($id);

        if ($employee['color'] != null || $employee['color'] === '') {
            $hex_color = $this->hexToRGB($employee['color']);

            $color = 'rgb('.implode(',', $hex_color).')';

            $employee['rgb_color'] = $color;
        }

        return response()->json([
            'employee' => $employee,
        ]);
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'last_name' => 'required',
            'first_name' => 'required',
            'birthday' => 'required',
            'job_id' => 'required',
            'color' => 'required',
        ]);

        if ($validator->passes()) {
            $employee = Employee::with('Job', 'Logs')->findOrFail($id);

            if ($employee['first_name'] !== $request['first_name']) {
                $this->Log(
                    'Updated '.$employee['first_name'].'\'s First Name to '.$request['first_name'], $this->authenticatedUser('id'));
            }

            if ($employee['middle_name'] !== $request['middle_name']) {
                $this->Log(
                    'Updated '.$employee['first_name'].'\'s Middle Name to '.$request['middle_name'],
                    $this->authenticatedUser('id')
                );
            }

            if ($employee['last_name'] !== $request['last_name']) {
                $this->Log(
                    'Updated '.$employee['first_name'].'\'s Last Name to '.$request['last_name'],
                    $this->authenticatedUser('id')
                );
            }

            if ($employee['job_id'] !== $request['job_id']) {
                $this->Log(
                    'Updated '.$employee['first_name'].'\'s Level from '.$employee->Job->title.' to '.Job::with('Logs')->where('id', $request['job_id'])->first()->title,
                    $this->authenticatedUser('id')
                );
            }

            if ($employee['color'] !== $request['color']) {
                $this->Log(
                    'Updated '.$employee['first_name'].'\'s User Color from '.$employee['color'].' to '.$request['color'],
                    $this->authenticatedUser('id')
                );
            }

            if ($employee['birthday'] !== $request['birthday']) {
                $this->Log(
                    'Updated '.$employee['first_name'].'\'s Birthday to '.$request['birthday'],
                    $this->authenticatedUser('id')
                );
            }

            $employee->update($request->only([
                'last_name',
                'first_name',
                'middle_name',
                'nickname',
                'email',
                'birthday',
                'color',
                'job_id',
            ]));

            if ($this->authenticatedUser('id') == $id) {
                $user = Employee::with('Job')->findOrFail($this->authenticatedUser('id'));

                return response()->json([
                    'status' => 'success',
                    'message' => 'Your information has been updated!',
                    'user' => $user,
                ]);
            } else {
                return response()->json([
                    'status' => 'success',
                    'message' => 'An employee\'s information has been updated!',
                ]);
            }
        }

        return $this->validationError($validator);
    }

    public function setStatus($id, Request $request)
    {
        $request->validate([
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        $authenticated_user = Auth::user();
        $user_level = (string) $authenticated_user->Job->level;

        if (! in_array($user_level, ['0', '1'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to change employee access.',
            ], 403);
        }

        if ((int) $authenticated_user->id === (int) $id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot change your own account status.',
            ], 422);
        }

        $employee = Employee::with('Job', 'Logs')->findOrFail($id);
        $status = (int) $request->input('status');

        if ((int) $employee->is_active === $status) {
            return response()->json([
                'status' => 'success',
                'message' => $status === 1
                    ? 'The employee account is already active.'
                    : 'The employee account is already inactive.',
                'employee' => [
                    'id' => $employee->id,
                    'is_active' => (int) $employee->is_active,
                ],
            ]);
        }

        $employee->is_active = $status;
        $employee->save();

        $action = $status === 1 ? 'Activated' : 'Deactivated';
        $this->Log(
            $action.' '.$employee->first_name.' '.$employee->last_name.'\'s BOCS account.',
            $authenticated_user->id
        );

        return response()->json([
            'status' => 'success',
            'message' => $status === 1
                ? 'The employee account has been activated.'
                : 'The employee account has been deactivated.',
            'employee' => [
                'id' => $employee->id,
                'is_active' => (int) $employee->is_active,
            ],
        ]);
    }

    public function destroy($id)
    {
        // Obsolete, no deletion. company policy
    }

    public function changePassword($id, Request $request)
    {
        $employee = Employee::with('Logs')->findOrFail($id);
        $authenticatedUser = Auth::user();
        $isOwnPassword = (int) $authenticatedUser->id === (int) $employee->id;
        $canResetPassword = in_array((string) $authenticatedUser->Job->level, ['0', '1'], true);

        if (! $isOwnPassword && ! $canResetPassword) {
            return response()->json([
                'status' => 'error',
                'code' => 'password_reset_forbidden',
                'message' => 'You are not authorized to reset this employee\'s password.',
            ], 403);
        }

        $rules = [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        if ($isOwnPassword) {
            $rules['current_password'] = ['required', 'string'];
        }

        $validation = Validator::make($request->all(), $rules);

        if ($validation->fails()) {
            return $this->validationError($validation);
        }

        $oldPassword = $employee->password;
        $usesBcrypt = is_string($oldPassword)
            && (password_get_info($oldPassword)['algoName'] ?? 'unknown') === 'bcrypt';

        if ($isOwnPassword && ! $usesBcrypt) {
            return response()->json([
                'status' => 'error',
                'code' => 'password_reset_required',
                'message' => 'The stored password is not a valid bcrypt hash and must be reset by an administrator.',
                'error' => [
                    'category' => 'legacy_password_error',
                    'stage' => 'verify_current_password',
                    'employee_id' => (int) $employee->id,
                    'suggestion' => 'Ask an administrator to reset this employee password.',
                ],
            ], 422);
        }

        if ($isOwnPassword && ! Hash::check($request->string('current_password'), $oldPassword)) {
            return response()->json([
                'status' => 'error',
                'code' => 'current_password_incorrect',
                'message' => 'The current password is incorrect.',
            ], 422);
        }

        if ($isOwnPassword && Hash::check($request->string('password'), $oldPassword)) {
            return response()->json([
                'status' => 'error',
                'code' => 'password_reused',
                'message' => 'The old password cannot be reused as the new password.',
            ], 422);
        }

        $employee->password = $request->string('password')->toString();
        $employee->save();
        $employee->tokens()->update(['revoked' => true]);

        $this->Log(
            ($isOwnPassword ? 'Changed ' : 'Reset ').$employee->first_name.'\'s password',
            $authenticatedUser->id
        );

        return response()->json([
            'status' => 'success',
            'message' => $isOwnPassword
                ? 'Password successfully changed! Please sign in again.'
                : 'Password successfully reset. The employee must sign in again.',
        ]);
    }
}

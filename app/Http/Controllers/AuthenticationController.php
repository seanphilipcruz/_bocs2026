<?php

namespace App\Http\Controllers;

use App\Http\Traits\LogTrait;
use App\Http\Traits\SystemDefaultsTrait;
use App\Models\Employee;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;

class AuthenticationController extends Controller
{
    use LogTrait;
    use SystemDefaultsTrait;

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $remember = (bool) ($validated['remember'] ?? false);
        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        $ip = $request->ip();
        $ipLocation = Location::get($ip);
        $location = $this->formatLocation($ip, $ipLocation);

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username and password does not match!',
                'code' => 'failed_attempt',
            ], 400);
        }

        $user_status = Auth::user()->is_active;

        if ($user_status === '0') {
            Auth::logout();

            return response()->json([
                'status' => 'error',
                'message' => 'Error logging in, contact administrator!',
                'code' => 'failed_attempt',
            ], 400);
        }

        $auth_user_id = $this->authenticatedUser('id');
        $user = Employee::with('Job')->findOrFail($auth_user_id);
        $tokenResult = $user->createToken($remember ? 'bocs-remembered' : 'bocs-session');
        $expiresAt = $remember
            ? now()->addDays(config('auth.token_lifetimes.remember_days'))
            : now()->addHours(config('auth.token_lifetimes.session_hours'));

        $tokenResult->token->expires_at = $expiresAt;
        $tokenResult->token->save();

        $this->Log('Successful login at IP: '.$ip.', Location: '.$location, $this->authenticatedUser('id'));

        return response()->json([
            'status' => 'success',
            'user' => $user,
            'access_token' => $tokenResult->accessToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'remember' => $remember,
        ]);
    }

    public function logout(Request $request)
    {
        $ip = $request->ip();
        $ipLocation = Location::get($ip);
        $location = $this->formatLocation($ip, $ipLocation);
        $user = $request->user();

        $this->Log('Successful logout at IP: '.$ip.', Location: '.$location, $this->authenticatedUser('id'));

        try {
            $user->token()->revoke();
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User logged out successfully!',
        ]);
    }

    private function formatLocation(string $ip, $location): string
    {
        $localAddresses = [
            '127.0.0.1',
            '172.19.0.1',
            '172.20.0.1',
            '192.168.65.1',
        ];

        if (in_array($ip, $localAddresses, true)) {
            return 'Local Machine';
        }

        if (! $location) {
            return 'Unknown Location';
        }

        return trim(sprintf(
            '%s, %s',
            $location->cityName ?? 'Unknown City',
            $location->zipCode ?? 'Unknown ZIP'
        ));
    }
}

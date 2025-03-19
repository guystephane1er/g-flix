<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\JsonResponse;

class AuthController extends BaseController
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['login', 'register', 'googleCallback']]);
    }

    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'referral_code' => Str::random(10),
                'trial_ends_at' => now()->addHours(24), // 24-hour free trial
                'status' => 'active'
            ]);

            $token = Auth::guard('api')->login($user);

            return $this->sendSuccess([
                'user' => $user,
                'authorization' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ], 'User successfully registered');
        } catch (\Exception $e) {
            return $this->sendError('Registration failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Login user and create token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return $this->sendUnauthorized('Invalid credentials');
        }

        $user = Auth::guard('api')->user();
        
        if ($user->status !== 'active') {
            Auth::guard('api')->logout();
            return $this->sendForbidden('Account is not active');
        }

        // Check device limit
        if (!$user->canConnectNewDevice()) {
            Auth::guard('api')->logout();
            return $this->sendError('Maximum device limit reached', [], 403);
        }

        // Increment connected devices
        $user->incrementConnectedDevices();

        return $this->sendSuccess([
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ], 'Successfully logged in');
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(): JsonResponse
    {
        $user = Auth::guard('api')->user();
        return $this->sendSuccess(['user' => $user]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(): JsonResponse
    {
        $user = Auth::guard('api')->user();
        $user->decrementConnectedDevices();
        Auth::guard('api')->logout();

        return $this->sendSuccess([], 'Successfully logged out');
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(): JsonResponse
    {
        return $this->sendSuccess([
            'user' => Auth::guard('api')->user(),
            'authorization' => [
                'token' => Auth::guard('api')->refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function googleRedirect(): JsonResponse
    {
        try {
            $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
            return $this->sendSuccess(['redirect_url' => $url]);
        } catch (\Exception $e) {
            return $this->sendError('Failed to generate Google auth URL', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Obtain the user information from Google.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function googleCallback(Request $request): JsonResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            $user = User::where('google_id', $googleUser->id)
                       ->orWhere('email', $googleUser->email)
                       ->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => Hash::make(Str::random(24)),
                    'referral_code' => Str::random(10),
                    'trial_ends_at' => now()->addHours(24), // 24-hour free trial
                    'status' => 'active'
                ]);
            } elseif (!$user->google_id) {
                $user->update(['google_id' => $googleUser->id]);
            }

            if ($user->status !== 'active') {
                return $this->sendForbidden('Account is not active');
            }

            if (!$user->canConnectNewDevice()) {
                return $this->sendError('Maximum device limit reached', [], 403);
            }

            $user->incrementConnectedDevices();
            $token = Auth::guard('api')->login($user);

            return $this->sendSuccess([
                'user' => $user,
                'authorization' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ], 'Successfully logged in with Google');

        } catch (\Exception $e) {
            return $this->sendError('Failed to authenticate with Google', ['error' => $e->getMessage()]);
        }
    }
}
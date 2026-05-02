<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Profile;
use App\Models\AuditLog;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Notifications\WelcomeMember;

class AuthController extends Controller
{
    // API Login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (Auth::attempt([$loginField => $request->login, 'password' => $request->password, 'is_active' => true])) {
            $user = Auth::user();

            // Check for 2FA
            if ($user->two_factor_enabled) {
                $this->sendTwoFactorCode($user);
                return response()->json([
                    'success' => true,
                    'message' => '2FA required',
                    'requires_2fa' => true,
                    'user_id' => $user->id
                ]);
            }

            // Create token
            $token = $user->createToken('API Token')->plainTextToken;

            // Log audit
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'login',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => $user->load('profile'),
                'token' => $token
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials or account inactive'
        ], 401);
    }

    // API Register
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string|unique:users',
            'password' => 'required|min:8|confirmed',
            'national_id' => 'required|string|unique:profiles',
            'gender' => 'nullable|in:male,female',
            'dob' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'member',
        ]);

        Profile::create([
            'user_id' => $user->id,
            'national_id' => $request->national_id,
            'gender' => $request->gender,
            'dob' => $request->dob,
            'join_date' => now(),
        ]);

        $user->assignRole('member');

        // Send welcome notification
        $user->notify(new WelcomeMember($user));

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'register',
            'ip_address' => $request->ip(),
        ]);

        // Create token
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'user' => $user->load('profile'),
            'token' => $token
        ], 201);
    }

    // Verify OTP
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        if ($user->two_factor_code === $request->otp && now()->lessThan($user->two_factor_expires_at)) {
            $user->update(['two_factor_code' => null, 'two_factor_expires_at' => null]);

            // Create token if not exists
            $token = $user->createToken('API Token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully',
                'user' => $user->load('profile'),
                'token' => $token
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired OTP'
        ], 401);
    }

    // Resend OTP
    public function resendOtp(Request $request)
    {
        $user = Auth::user();

        if (!$user->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => '2FA not enabled'
            ], 400);
        }

        $this->sendTwoFactorCode($user);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully'
        ]);
    }

    // Send reset link
    public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $token = Password::createToken($user);
            // Send email with reset link
            // For now, just return success
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent if email exists'
        ]);
    }

    // Reset password
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid token or email'
        ], 400);
    }

    // Change password
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'password_change',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        $user = Auth::user();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'logout',
            'ip_address' => $request->ip(),
        ]);

        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    // Send 2FA code
    private function sendTwoFactorCode($user)
    {
        $code = rand(100000, 999999);
        $user->update([
            'two_factor_code' => $code,
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);

        // Send SMS via Africa's Talking or similar
        // For now, just log it
        \Log::info("2FA Code for {$user->phone}: {$code}");

        return $code;
    }
}
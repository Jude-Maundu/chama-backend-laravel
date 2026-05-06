<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Profile;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\Chama;
use App\Models\ChamaMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use App\Http\Controllers\Controller;
use App\Notifications\WelcomeMember;

class AuthController extends Controller
{
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

            if ($user->two_factor_enabled) {
                $this->sendTwoFactorCode($user);
                return response()->json([
                    'success' => true,
                    'message' => '2FA required',
                    'requires_2fa' => true,
                    'user_id' => $user->id
                ]);
            }

            $token = $user->createToken('API Token')->plainTextToken;

            $auditLog = new AuditLog();
            $auditLog->user_id = $user->id;
            $auditLog->action = 'login';
            $auditLog->ip_address = $request->ip();
            $auditLog->user_agent = $request->userAgent();
            $auditLog->save();

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => $user->load('profile', 'currentChama')->makeHidden(['password'])->toArray() + [
                    'roles' => $user->getRoleNames()->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray()
                ],
                'token' => $token
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials or account inactive'
        ], 401);
    }

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
            'chama_action' => 'required|in:create,join',
            'chama_name' => 'required_if:chama_action,create|string|max:255|unique:chamas,name',
            'chama_description' => 'nullable|string',
            'join_code' => 'required_if:chama_action,join|string|exists:chamas,join_code',
        ]);

        if ($validator->fails()) {
            \Log::warning('Registration validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request' => $request->except(['password', 'password_confirmation'])
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

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

        if ($request->chama_action === 'create') {
            $chama = Chama::create([
                'name' => $request->chama_name,
                'slug' => Str::slug($request->chama_name) . '-' . Str::random(5),
                'join_code' => strtoupper(Str::random(8)),
                'description' => $request->chama_description,
                'created_by' => $user->id,
                'status' => 'active',
            ]);

            ChamaMember::create([
                'chama_id' => $chama->id,
                'user_id' => $user->id,
                'role' => 'admin',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            $user->update(['current_chama_id' => $chama->id]);
            $user->assignRole('chama-admin');
        } elseif ($request->chama_action === 'join') {
            $chama = Chama::where('join_code', $request->join_code)->first();

            $existing = ChamaMember::where('chama_id', $chama->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$existing) {
                ChamaMember::create([
                    'chama_id' => $chama->id,
                    'user_id' => $user->id,
                    'role' => 'member',
                    'status' => 'active',
                    'joined_at' => now(),
                ]);
            }

            $user->update(['current_chama_id' => $chama->id]);
            $user->assignRole('member');
        } else {
            $user->assignRole('member');
        }

        $user->notify(new WelcomeMember($user));

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'register',
            'ip_address' => $request->ip(),
        ]);

        if (isset($chama)) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => $request->chama_action === 'create' ? 'create_chama' : 'join_chama',
                'record_id' => $chama->id,
                'ip_address' => $request->ip(),
            ]);
        }

        DB::commit();

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'user' => $user->load('profile', 'currentChama'),
            'token' => $token
        ], 201);
    }

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

    // Send 2FA code
    private function sendTwoFactorCode($user)
    {
        $code = rand(100000, 999999);
        $user->update([
            'two_factor_code' => $code,
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);
        
        // Send SMS via Africa's Talking
        // $this->sendSms($user->phone, "Your Chama verification code is: $code");
        
        return $code;
    }

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
}

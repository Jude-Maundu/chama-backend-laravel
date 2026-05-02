<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user()->load('profile');
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|unique:users,phone,' . $user->id,
        ]);

        $user->update($request->only(['name', 'email', 'phone']));
        
        if ($user->profile) {
            $user->profile->update($request->only([
                'gender', 'occupation', 'address', 'city', 'postal_code',
                'emergency_contact_name', 'emergency_contact_phone',
                'national_id', 'dob'
            ]));
        } else {
            $user->profile()->create($request->only([
                'gender', 'occupation', 'address', 'city', 'postal_code',
                'emergency_contact_name', 'emergency_contact_phone',
                'national_id', 'dob'
            ]));
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->load('profile')
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();
        $profile = $user->profile ?? $user->profile()->create(['user_id' => $user->id]);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($profile->profile_photo) {
                Storage::disk('public')->delete($profile->profile_photo);
            }

            $path = $request->file('photo')->store('profiles/photos', 'public');
            $profile->update(['profile_photo' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Profile photo uploaded successfully',
                'photo_url' => asset('storage/' . $path)
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No file uploaded'
        ], 400);
    }

    public function uploadDocument(Request $request)
    {
        $request->validate([
            'document' => 'required|mimes:pdf,doc,docx,jpg,png|max:5120',
            'type' => 'required|string|in:national_id,kra_pin,other'
        ]);

        $user = Auth::user();
        $profile = $user->profile ?? $user->profile()->create(['user_id' => $user->id]);

        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('profiles/documents', 'public');
            
            // For now just storing the path, you might want a documents table
            $profile->update(['id_document' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'path' => $path
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No file uploaded'
        ], 400);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
        ]);
    }

    public function enableTwoFactor(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'phone' => 'required|regex:/^254[0-9]{9}$/',
        ]);

        $user->update([
            'two_factor_enabled' => true,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication enabled'
        ]);
    }

    public function disableTwoFactor()
    {
        Auth::user()->update(['two_factor_enabled' => false]);
        
        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication disabled'
        ]);
    }

    public function updateBank(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string',
            'bank_account_number' => 'required|string',
            'mpesa_number' => 'nullable|regex:/^254[0-9]{9}$/',
        ]);

        $profile = Auth::user()->profile;
        
        if (!$profile) {
            $profile = Profile::create([
                'user_id' => Auth::id(),
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'mpesa_number' => $request->mpesa_number,
            ]);
        } else {
            $profile->update($request->only(['bank_name', 'bank_account_number', 'mpesa_number']));
        }

        return response()->json([
            'success' => true,
            'message' => 'Bank details updated successfully',
            'data' => $profile
        ]);
    }
}
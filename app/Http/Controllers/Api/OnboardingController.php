<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chama;
use App\Models\ChamaMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function checkOnboarding()
    {
        $user = Auth::user();
        $hasChama = ChamaMember::where('user_id', $user->id)->exists();

        return response()->json([
            'needs_onboarding' => !$hasChama,
            'user' => $user
        ]);
    }

    public function createChama(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:chamas,name',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();

            $chama = Chama::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name) . '-' . Str::random(5),
                'join_code' => strtoupper(Str::random(8)),
                'description' => $request->description,
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Chama created successfully',
                'data' => $chama,
                'user' => $user->load('profile')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function joinChama(Request $request)
    {
        $request->validate([
            'join_code' => 'required|string|exists:chamas,join_code',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $chama = Chama::where('join_code', $request->join_code)->first();

            // Check if already a member
            $existing = ChamaMember::where('chama_id', $chama->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                $user->update(['current_chama_id' => $chama->id]);
                $user->assignRole('member');
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'You are already a member of this Chama',
                    'data' => $chama,
                    'user' => $user->load('profile')
                ]);
            }

            ChamaMember::create([
                'chama_id' => $chama->id,
                'user_id' => $user->id,
                'role' => 'member',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            $user->update(['current_chama_id' => $chama->id]);
            $user->assignRole('member');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully joined the Chama',
                'data' => $chama,
                'user' => $user->load('profile')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

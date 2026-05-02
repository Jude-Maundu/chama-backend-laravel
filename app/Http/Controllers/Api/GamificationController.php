<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chama;
use App\Models\MemberProfile;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GamificationController extends Controller
{
    /**
     * Savings League / Leaderboard (Feature 40)
     */
    public function leaderboard(Chama $chama)
    {
        $leaderboard = DB::table('contributions')
                        ->join('users', 'contributions.user_id', '=', 'users.id')
                        ->where('contributions.chama_id', $chama->id)
                        ->where('contributions.status', 'completed')
                        ->select('users.name', DB::raw('SUM(amount) as total_saved'))
                        ->groupBy('users.id', 'users.name')
                        ->orderByDesc('total_saved')
                        ->get();

        return response()->json(['success' => true, 'data' => $leaderboard]);
    }

    /**
     * Loyalty Points (Feature 41)
     */
    public function getLoyaltyPoints(Chama $chama)
    {
        $points = MemberProfile::where('chama_id', $chama->id)
                              ->where('user_id', auth()->id())
                              ->first();

        return response()->json([
            'success' => true, 
            'points' => $points ? $points->loyalty_points : 0
        ]);
    }

    /**
     * Lottery Savings (Feature 42)
     */
    public function lotteryDraw(Chama $chama)
    {
        // Simple lottery: random user from active members
        $winner = $chama->members()->inRandomOrder()->first();

        return response()->json([
            'success' => true,
            'winner' => $winner,
            'prize' => 'Mock Prize (KES 1000)',
            'drawn_at' => now()
        ]);
    }

    /**
     * Achievement Badges (Feature 40/41)
     */
    public function getBadges(Chama $chama)
    {
        // Mock badges
        $badges = [
            ['name' => 'Early Bird', 'description' => 'Contributed before due date 3 times in a row', 'earned' => true],
            ['name' => 'Perfect Attendance', 'description' => 'Attended all meetings in the last 6 months', 'earned' => false],
            ['name' => 'Big Saver', 'description' => 'Saved more than KES 50,000 total', 'earned' => true],
        ];

        return response()->json(['success' => true, 'data' => $badges]);
    }
}

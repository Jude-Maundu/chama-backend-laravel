<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Contribution;
use App\Models\User;

class HomeController extends Controller
{
    public function index()
    {
        $upcomingMeetings = Meeting::upcoming()->take(3)->get();
        $totalMembers = User::role('member')->count();
        $totalContributions = Contribution::where('status', 'completed')->sum('total_amount');
        
        return view('home', compact('upcomingMeetings', 'totalMembers', 'totalContributions'));
    }
}
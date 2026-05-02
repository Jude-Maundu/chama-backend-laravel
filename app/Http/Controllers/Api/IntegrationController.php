<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chama;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    /**
     * USSD Integration Hook (Feature 37)
     */
    public function ussd(Request $request)
    {
        // Simple USSD logic (mock)
        $sessionId = $request->sessionId;
        $phoneNumber = $request->phoneNumber;
        $text = $request->text;

        if ($text == "") {
            $response = "CON Welcome to Chama App\n";
            $response .= "1. Check Balance\n";
            $response .= "2. Last Contribution";
        } else if ($text == "1") {
            $response = "END Your current balance is KES 5,200";
        } else if ($text == "2") {
            $response = "END Your last contribution was KES 1,000 on 2026-04-10";
        } else {
            $response = "END Invalid option";
        }

        return response($response)->header('Content-Type', 'text/plain');
    }

    /**
     * WhatsApp Bot Hook (Feature 38)
     */
    public function whatsapp(Request $request)
    {
        // Mock WhatsApp message handling
        $message = $request->input('Body');
        $from = $request->input('From');

        \Log::info("WhatsApp message received from {$from}: {$message}");

        return response()->json([
            'success' => true,
            'reply' => "You said: {$message}. We have received your request."
        ]);
    }

    /**
     * SMS Credit System (Feature 56)
     */
    public function getSmsCredits(Chama $chama)
    {
        // Mock SMS credits
        return response()->json([
            'success' => true,
            'credits' => 1500,
            'rate' => '1.5 KES per SMS'
        ]);
    }

    public function purchaseSmsCredits(Request $request, Chama $chama)
    {
        $request->validate(['amount' => 'required|numeric|min:100']);
        
        // Mock purchase logic
        return response()->json([
            'success' => true,
            'message' => 'SMS credits purchased successfully',
            'new_balance' => 2500
        ]);
    }
}

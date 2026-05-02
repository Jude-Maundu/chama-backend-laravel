<?php

namespace App\Http\Controllers\Api;

use App\Models\MemberWallet;
use App\Models\WalletTransfer;
use App\Models\Chama;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WalletController extends Controller
{
    /**
     * Get member's wallets for a Chama
     */
    public function index(Chama $chama)
    {
        $wallets = MemberWallet::where('user_id', auth()->id())
                               ->where('chama_id', $chama->id)
                               ->get();

        return response()->json($wallets);
    }

    /**
     * Add new wallet
     */
    public function store(Request $request, Chama $chama)
    {
        $validated = $request->validate([
            'wallet_type' => 'required|in:mpesa,airtel_money,tigo_pesa,internal',
            'external_wallet_id' => 'sometimes|string',
            'is_primary' => 'sometimes|boolean',
        ]);

        $wallet = MemberWallet::create([
            'user_id' => auth()->id(),
            'chama_id' => $chama->id,
            ...$validated
        ]);

        return response()->json($wallet, 201);
    }

    /**
     * Get wallet details
     */
    public function show(MemberWallet $wallet)
    {
        $this->authorize('view', $wallet);
        return response()->json($wallet);
    }

    /**
     * Update wallet
     */
    public function update(Request $request, MemberWallet $wallet)
    {
        $this->authorize('update', $wallet);

        $validated = $request->validate([
            'is_primary' => 'sometimes|boolean',
            'status' => 'sometimes|in:active,inactive,suspended',
        ]);

        $wallet->update($validated);
        return response()->json($wallet);
    }

    /**
     * Transfer between wallets
     */
    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'from_wallet_id' => 'required|exists:member_wallets,id',
            'to_wallet_id' => 'required|exists:member_wallets,id',
            'amount' => 'required|numeric|min:0',
            'notes' => 'sometimes|string',
        ]);

        $fromWallet = MemberWallet::findOrFail($validated['from_wallet_id']);
        $this->authorize('update', $fromWallet);

        $transfer = WalletTransfer::create([
            ...$validated,
            'chama_id' => $fromWallet->chama_id,
        ]);

        try {
            $transfer->processTransfer();
            return response()->json(['message' => 'Transfer successful', 'transfer' => $transfer], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get wallet transfer history
     */
    public function transfers(MemberWallet $wallet)
    {
        $this->authorize('view', $wallet);

        $transfers = WalletTransfer::where('from_wallet_id', $wallet->id)
                                   ->orWhere('to_wallet_id', $wallet->id)
                                   ->latest()
                                   ->paginate(20);

        return response()->json($transfers);
    }
}

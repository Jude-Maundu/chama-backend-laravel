<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\MpesaTransaction;
use App\Models\Contribution;
use App\Models\Transaction;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    protected $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    public function stkPush(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^254[0-9]{9}$/',
            'amount' => 'required|numeric|min:1',
            'account_reference' => 'required|string',
        ]);

        $response = $this->mpesaService->stkPush(
            $request->phone,
            $request->amount,
            $request->account_reference,
            'Chama Payment'
        );

        if ($response && isset($response['CheckoutRequestID'])) {
            MpesaTransaction::create([
                'merchant_request_id' => $response['MerchantRequestID'] ?? null,
                'checkout_request_id' => $response['CheckoutRequestID'],
                'amount' => $request->amount,
                'phone_number' => $request->phone,
                'transaction_type' => 'stkpush',
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'checkout_id' => $response['CheckoutRequestID'],
                'message' => 'STK Push sent successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to initiate STK Push'
        ], 500);
    }

    public function callback(Request $request)
    {
        Log::info('M-Pesa Callback Received', $request->all());

        $data = $request->input('Body.stkCallback');
        
        $checkoutRequestId = $data['CheckoutRequestID'];
        $resultCode = $data['ResultCode'];
        $resultDesc = $data['ResultDesc'];

        $mpesaTransaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

        if ($mpesaTransaction) {
            $mpesaTransaction->update([
                'status' => $resultCode == 0 ? 'completed' : 'failed',
                'result_code' => $resultCode,
                'result_description' => $resultDesc,
                'mpesa_receipt_number' => $data['CallbackMetadata']['Item'][0]['Value'] ?? null,
                'callback_data' => $data,
            ]);

            if ($resultCode == 0 && $mpesaTransaction->reference_type === 'contribution') {
                $this->completeContribution($mpesaTransaction);
            }
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }

    private function completeContribution($mpesaTransaction)
    {
        $contribution = Contribution::where('transaction_id', $mpesaTransaction->checkout_request_id)->first();
        
        if ($contribution) {
            $contribution->update([
                'status' => 'completed',
                'mpesa_receipt' => $mpesaTransaction->mpesa_receipt_number,
            ]);

            Transaction::create([
                'user_id' => $contribution->user_id,
                'type' => 'contribution',
                'direction' => 'credit',
                'amount' => $contribution->total_amount,
                'description' => 'M-Pesa Contribution',
                'transaction_date' => now(),
                'created_by' => $contribution->recorded_by,
            ]);
        }
    }

    public function status($checkoutId)
    {
        $transaction = MpesaTransaction::where('checkout_request_id', $checkoutId)->first();
        
        if (!$transaction) {
            return response()->json(['status' => 'not_found']);
        }

        return response()->json([
            'status' => $transaction->status,
            'receipt' => $transaction->mpesa_receipt_number,
        ]);
    }

    public function b2c(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^254[0-9]{9}$/',
            'amount' => 'required|numeric|min:1',
            'remarks' => 'required|string',
        ]);

        $response = $this->mpesaService->b2c(
            $request->phone,
            $request->amount,
            $request->remarks
        );

        if ($response && isset($response['ConversationID'])) {
            MpesaTransaction::create([
                'conversation_id' => $response['ConversationID'],
                'amount' => $request->amount,
                'phone_number' => $request->phone,
                'transaction_type' => 'b2c',
                'status' => 'pending',
            ]);

            return response()->json(['success' => true, 'conversation_id' => $response['ConversationID']]);
        }

        return response()->json(['success' => false], 500);
    }

    public function reconcile()
    {
        // Reconcile M-Pesa transactions with bank statement
        $pendingTransactions = MpesaTransaction::where('status', 'pending')->get();
        
        return response()->json([
            'success' => true,
            'pending_transactions' => $pendingTransactions
        ]);
    }

    public function balance()
    {
        $balance = $this->mpesaService->checkBalance();
        return response()->json(['balance' => $balance]);
    }
}
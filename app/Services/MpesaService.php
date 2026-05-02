<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MpesaService
{
    protected $consumerKey;
    protected $consumerSecret;
    protected $businessCode;
    protected $passKey;
    protected $callbackUrl;
    protected $b2cSecurityCredential;
    protected $b2cCommandId = 'BusinessPayment';
    protected $b2cQueueTimeoutUrl;
    protected $b2cResultUrl;
    protected $stkTimeout = 30; // seconds
    protected $baseUrl = 'https://sandbox.safaricom.co.ke'; // Change to production URL when live

    public function __construct()
    {
        $this->consumerKey = config('mpesa.consumer_key');
        $this->consumerSecret = config('mpesa.consumer_secret');
        $this->businessCode = config('mpesa.shortcode');
        $this->passKey = config('mpesa.passkey');
        $this->callbackUrl = config('mpesa.callback_url');
        $this->b2cSecurityCredential = config('mpesa.security_credential');
        $this->b2cQueueTimeoutUrl = config('mpesa.b2c.queue_timeout_url');
        $this->b2cResultUrl = config('mpesa.b2c.result_url');
        
        // Use production URL if not in local/testing environment
        if (config('mpesa.environment') === 'production') {
            $this->baseUrl = 'https://api.safaricom.co.ke';
        }
    }

    /**
     * Get M-Pesa access token
     */
    public function getAccessToken()
    {
        $cacheKey = 'mpesa_access_token';
        
        // Return cached token if valid
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get($this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials');

            if ($response->successful() && isset($response['access_token'])) {
                $token = $response['access_token'];
                $expiresIn = $response['expires_in'] ?? 3599; // Cache for 59 minutes if not specified
                
                Cache::put($cacheKey, $token, $expiresIn - 60); // Refresh 60 seconds before expiry
                
                return $token;
            }

            Log::error('Failed to get M-Pesa access token', $response->json());
            return null;
        } catch (\Exception $e) {
            Log::error('M-Pesa token error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * STK Push - Initiate payment request (customer pays)
     */
    public function stkPush($phone, $amount, $accountReference, $description)
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            Log::error('Cannot get M-Pesa access token for STK Push');
            return null;
        }

        try {
            $timestamp = now()->format('YmdHis');
            $password = base64_encode($this->businessCode . $this->passKey . $timestamp);

            $response = Http::withToken($token)
                ->post($this->baseUrl . '/mpesa/stkpush/v1/processrequest', [
                    'BusinessShortCode' => $this->businessCode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => (int)$amount,
                    'PartyA' => $phone,
                    'PartyB' => $this->businessCode,
                    'PhoneNumber' => $phone,
                    'CallBackURL' => $this->callbackUrl,
                    'AccountReference' => $accountReference,
                    'TransactionDesc' => $description,
                ]);

            Log::info('STK Push request sent', [
                'phone' => $phone,
                'amount' => $amount,
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('STK Push failed', $response->json());
            return null;
        } catch (\Exception $e) {
            Log::error('STK Push exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * B2C - Business to Customer payment
     */
    public function b2c($phone, $amount, $remarks)
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            Log::error('Cannot get M-Pesa access token for B2C');
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->post($this->baseUrl . '/mpesa/b2c/v3/paymentrequest', [
                    'OriginatorConversationID' => $this->generateConversationId(),
                    'InitiatorName' => config('mpesa.initiator_name'),
                    'SecurityCredential' => $this->b2cSecurityCredential,
                    'CommandID' => $this->b2cCommandId,
                    'Amount' => (int)$amount,
                    'PartyA' => $this->businessCode,
                    'PartyB' => $phone,
                    'Remarks' => $remarks,
                    'QueueTimeOutURL' => $this->b2cQueueTimeoutUrl,
                    'ResultURL' => $this->b2cResultUrl,
                ]);

            Log::info('B2C request sent', [
                'phone' => $phone,
                'amount' => $amount,
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('B2C failed', $response->json());
            return null;
        } catch (\Exception $e) {
            Log::error('B2C exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Query transaction status
     */
    public function queryStatus($checkoutRequestId)
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            Log::error('Cannot get M-Pesa access token for query status');
            return null;
        }

        try {
            $timestamp = now()->format('YmdHis');
            $password = base64_encode($this->businessCode . $this->passKey . $timestamp);

            $response = Http::withToken($token)
                ->post($this->baseUrl . '/mpesa/stkpushquery/v1/query', [
                    'BusinessShortCode' => $this->businessCode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'CheckoutRequestID' => $checkoutRequestId,
                ]);

            Log::info('Query status response', [
                'checkout_id' => $checkoutRequestId,
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Query status failed', $response->json());
            return null;
        } catch (\Exception $e) {
            Log::error('Query status exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check account balance
     */
    public function checkBalance()
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            Log::error('Cannot get M-Pesa access token for balance check');
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->post($this->baseUrl . '/mpesa/accountbalance/v1/query', [
                    'Initiator' => config('mpesa.initiator_name'),
                    'SecurityCredential' => $this->b2cSecurityCredential,
                    'CommandID' => 'AccountBalance',
                    'PartyA' => $this->businessCode,
                    'IdentifierType' => '4',
                    'Remarks' => 'Balance Check',
                    'QueueTimeOutURL' => $this->b2cQueueTimeoutUrl,
                    'ResultURL' => $this->b2cResultUrl,
                ]);

            Log::info('Balance check response', $response->json());

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Balance check failed', $response->json());
            return null;
        } catch (\Exception $e) {
            Log::error('Balance check exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate unique conversation ID
     */
    protected function generateConversationId()
    {
        return uniqid('CHAMA_', true);
    }
}

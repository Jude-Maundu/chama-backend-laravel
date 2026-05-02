<?php

namespace App\Http\Controllers\Api;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CurrencyController extends Controller
{
    /**
     * Get all active currencies
     */
    public function index()
    {
        return response()->json(
            Currency::where('is_active', true)->get()
        );
    }

    /**
     * Get currency with exchange rates
     */
    public function show(Currency $currency)
    {
        return response()->json([
            'currency' => $currency,
            'exchange_rates' => $currency->exchangeRatesFrom()
                                         ->with('toCurrency')
                                         ->get()
        ]);
    }

    /**
     * Convert amount between currencies
     */
    public function convert(Request $request)
    {
        $validated = $request->validate([
            'from_currency_id' => 'required|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
        ]);

        try {
            $converted = ExchangeRate::convert(
                $validated['amount'],
                $validated['from_currency_id'],
                $validated['to_currency_id']
            );

            return response()->json([
                'original_amount' => $validated['amount'],
                'converted_amount' => round($converted, 2),
                'from_currency_id' => $validated['from_currency_id'],
                'to_currency_id' => $validated['to_currency_id'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Conversion failed'], 400);
        }
    }

    /**
     * Update exchange rates
     */
    public function updateExchangeRates(Request $request)
    {
        $this->authorize('update-settings', auth()->user());

        $validated = $request->validate([
            'rates' => 'required|array',
            'rates.*.from_currency_id' => 'required|exists:currencies,id',
            'rates.*.to_currency_id' => 'required|exists:currencies,id',
            'rates.*.rate' => 'required|numeric|min:0',
        ]);

        foreach ($validated['rates'] as $rate) {
            ExchangeRate::updateOrCreate(
                [
                    'from_currency_id' => $rate['from_currency_id'],
                    'to_currency_id' => $rate['to_currency_id'],
                ],
                [
                    'rate' => $rate['rate'],
                    'fetched_at' => now(),
                    'source' => 'manual'
                ]
            );
        }

        return response()->json(['message' => 'Exchange rates updated']);
    }
}

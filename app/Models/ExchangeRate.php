<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = ['from_currency_id', 'to_currency_id', 'rate', 'fetched_at', 'source'];

    protected $casts = [
        'fetched_at' => 'datetime',
    ];

    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }

    /**
     * Convert amount from one currency to another
     */
    public static function convert($amount, $fromCurrencyId, $toCurrencyId): float
    {
        if ($fromCurrencyId == $toCurrencyId) {
            return $amount;
        }

        $rate = static::where('from_currency_id', $fromCurrencyId)
                      ->where('to_currency_id', $toCurrencyId)
                      ->latest('fetched_at')
                      ->first();

        if (!$rate) {
            // Attempt to fetch from API if not found
            try {
                $fromCurrency = Currency::find($fromCurrencyId);
                $toCurrency = Currency::find($toCurrencyId);
                return static::fetchAndConvert($amount, $fromCurrency->code, $toCurrency->code);
            } catch (\Exception $e) {
                throw new \Exception('Exchange rate not found and fetching failed');
            }
        }

        return $amount * $rate->rate;
    }

    /**
     * Fetch and convert using external API (Feature 1)
     */
    public static function fetchAndConvert($amount, $fromCode, $toCode)
    {
        $response = Http::get("https://api.exchangerate-api.com/v4/latest/{$fromCode}");
        
        if ($response->successful()) {
            $rates = $response->json()['rates'];
            if (isset($rates[$toCode])) {
                $rateValue = $rates[$toCode];
                
                // Store for future use
                $fromCurrency = Currency::where('code', $fromCode)->first();
                $toCurrency = Currency::where('code', $toCode)->first();
                
                if ($fromCurrency && $toCurrency) {
                    static::updateOrCreate(
                        ['from_currency_id' => $fromCurrency->id, 'to_currency_id' => $toCurrency->id],
                        ['rate' => $rateValue, 'fetched_at' => now(), 'source' => 'api']
                    );
                }
                
                return $amount * $rateValue;
            }
        }
        
        throw new \Exception("Could not fetch exchange rate for {$fromCode} to {$toCode}");
    }
}

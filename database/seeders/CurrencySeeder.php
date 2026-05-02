<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            ['code' => 'KES', 'name' => 'Kenyan Shilling', 'symbol' => 'Sh', 'decimal_places' => 2],
            ['code' => 'UGX', 'name' => 'Ugandan Shilling', 'symbol' => 'USh', 'decimal_places' => 0],
            ['code' => 'TZS', 'name' => 'Tanzanian Shilling', 'symbol' => 'Sh', 'decimal_places' => 2],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }

        // Set default exchange rates (KES as base)
        $this->seedExchangeRates();
    }

    private function seedExchangeRates(): void
    {
        // Note: These are placeholder rates. Update with real rates from API
        $kesToCurrencies = [
            'UGX' => 43.50, // 1 KES = 43.50 UGX
            'TZS' => 33.45, // 1 KES = 33.45 TZS
            'USD' => 0.0078, // 1 KES = 0.0078 USD
            'EUR' => 0.0072, // 1 KES = 0.0072 EUR
            'GBP' => 0.0062, // 1 KES = 0.0062 GBP
        ];

        $kesId = Currency::where('code', 'KES')->first()?->id;
        
        if (!$kesId) {
            return;
        }

        foreach ($kesToCurrencies as $currencyCode => $rate) {
            $targetCurrency = Currency::where('code', $currencyCode)->first();
            
            if ($targetCurrency) {
                \App\Models\ExchangeRate::updateOrCreate(
                    [
                        'from_currency_id' => $kesId,
                        'to_currency_id' => $targetCurrency->id,
                    ],
                    [
                        'rate' => $rate,
                        'fetched_at' => now(),
                        'source' => 'seed_data',
                    ]
                );
            }
        }
    }
}

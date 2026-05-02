<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chama;
use App\Models\Dividend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxController extends Controller
{
    /**
     * Tax Reporting (Feature 26)
     */
    public function summary(Chama $chama)
    {
        $year = request('year', now()->year);

        $dividends = Dividend::where('chama_id', $chama->id)
                            ->whereYear('created_at', $year)
                            ->get();

        $totalDividends = $dividends->sum('amount');
        $withholdingTax = $totalDividends * 0.05; // 5% mock tax rate

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'total_dividends' => $totalDividends,
                'withholding_tax_total' => round($withholdingTax, 2),
                'tax_rate' => '5%',
                'status' => 'compliant'
            ]
        ]);
    }

    public function calculateWithholding(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:dividend,interest'
        ]);

        $rate = $request->type === 'dividend' ? 0.05 : 0.15;
        $tax = $request->amount * $rate;

        return response()->json([
            'success' => true,
            'amount' => $request->amount,
            'tax_amount' => round($tax, 2),
            'net_amount' => round($request->amount - $tax, 2),
            'rate' => ($rate * 100) . '%'
        ]);
    }
}

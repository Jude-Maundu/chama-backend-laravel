<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Contribution;
use App\Models\Loan;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateMemberStatements extends Command
{
    protected $signature = 'chama:generate-member-statements
                            {--month= : Month to generate statements for}
                            {--year= : Year to generate statements for}
                            {--member= : Specific member ID}';
    
    protected $description = 'Generate monthly statements for all members';

    public function handle()
    {
        $this->info('📄 Generating member statements...');
        
        $month = $this->option('month') ?? now()->month;
        $year = $this->option('year') ?? now()->year;
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        
        $members = $this->option('member') 
            ? User::where('id', $this->option('member'))->get()
            : User::role('member')->where('is_active', true)->get();
        
        $statementDir = storage_path('app/statements/' . $year . '/' . str_pad($month, 2, '0', STR_PAD_LEFT));
        if (!file_exists($statementDir)) {
            mkdir($statementDir, 0755, true);
        }
        
        $generatedCount = 0;
        
        foreach ($members as $member) {
            $contributions = $member->contributions()
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->get();
            
            $loans = $member->loans()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            
            $transactions = $member->transactions()
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->get();
            
            $openingBalance = $member->transactions()
                ->where('transaction_date', '<', $startDate)
                ->sum('amount');
            
            $closingBalance = $openingBalance + $contributions->sum('total_amount') - $loans->sum('amount');
            
            $data = [
                'member' => $member,
                'month' => $month,
                'year' => $year,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'contributions' => $contributions,
                'loans' => $loans,
                'transactions' => $transactions,
                'opening_balance' => $openingBalance,
                'closing_balance' => $closingBalance,
            ];
            
            $pdf = Pdf::loadView('pdf.member-statement', $data);
            $filename = "statement_{$member->id}_{$year}_{$month}.pdf";
            $pdf->save($statementDir . '/' . $filename);
            
            $generatedCount++;
            
            if ($generatedCount % 10 === 0) {
                $this->line("   • Generated {$generatedCount} statements...");
            }
        }
        
        $this->newLine();
        $this->info("✅ Generated {$generatedCount} member statement(s)");
        $this->info("📁 Location: storage/app/statements/{$year}/" . str_pad($month, 2, '0', STR_PAD_LEFT));
        
        return Command::SUCCESS;
    }
}
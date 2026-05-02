<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('chama:status', function () {
    $this->info('Chama System Status:');
    $this->info('---------------------');
    $this->info('Database: ' . (app()->environment('production') ? 'Production' : 'Development'));
    $this->info('Members: ' . \App\Models\User::role('member')->count());
    $this->info('Total Contributions: ' . number_format(\App\Models\Contribution::where('status', 'completed')->sum('total_amount'), 2));
    $this->info('Active Loans: ' . number_format(\App\Models\Loan::whereIn('status', ['approved', 'disbursed'])->sum('balance'), 2));
})->purpose('Display Chama system status');

<?php

namespace App\Console\Commands;

use App\Models\Recurrency;
use Illuminate\Console\Command;

class GenerateRecurringEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurrency:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating recurring events...');

        $recurrencies =  Recurrency::whereNull('end')
            ->orWhere('end', '>=', now())
            ->get();

        foreach ($recurrencies as $recurrency) {
            $recurrency->generateEvents();
        }

        $this->info('Recurring events generated successfully.');
    }
}

<?php

namespace App\Console\Commands;

use App\Models\TransactionType;
use Illuminate\Console\Command;

class CreateTransactionType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:transaction-type {name} {slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create transaction type';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating transaction type...');

        //create transaction type
        $transactionType = new TransactionType();
        $transactionType->name = $this->argument('name');
        $transactionType->slug = $this->argument('slug');
        $transactionType->save();

        $this->info('Transaction type created successfully!');
    }
}

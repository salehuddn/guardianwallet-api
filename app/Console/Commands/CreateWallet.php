<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-wallet';

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
        //create walet for user that does not have wallet
        $users = User::doesntHave('wallet')->get();

        foreach ($users as $user) {
            $user->wallet()->create([
                'balance' => 0.00,
                'status' => 'active',
            ]);
        }

        $this->info('Wallets created successfully');
    }
}

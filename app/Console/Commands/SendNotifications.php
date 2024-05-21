<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications to users.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //send notifications for users role dependant almost exceed spending limit (e.g. 70% of the limit)
        $users = User::where('role', 'dependant')->get();

        foreach ($users as $user) {
            if ($user->spendingLimit * 0.7 <= $user->spending) {
                
            }
        }
    }
}

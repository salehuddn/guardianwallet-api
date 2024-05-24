<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Services\SpendingService;

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
        $users = User::role('dependant')->get();

        foreach ($users as $user) {
            $result = SpendingService::hasAlmostExceededLimit($user);
            if ($result && $result['code'] === '200') {
                dd($result['message']);
                // Send notification to the user
                $this->sendNotification($user, $result['message']);
            }
        }
    }
}

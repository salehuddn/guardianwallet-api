<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Services\SpendingService;
use App\Notifications\SpendingLimitNotification;

class CheckDependantSpendingLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:spending-limit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check dependant spending limit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::role('dependant')->get();

        foreach ($users as $user) {
            $result = SpendingService::hasAlmostExceededLimit($user);

            // if the result is null, skip this iteration
            if (!$result) {
                continue;
            }

            if ($result['code'] === '200') {
                $title = 'Spending Limit Alert';
                
                // notify the user with the appropriate title
                $user->notify(new SpendingLimitNotification($title, $result['message']));
                
                // notify the guardian if it's a dependant
                if ($user->hasRole('dependant')) {
                    $guardian = $user->guardians()->first();
                    $title = "User: {$user->name}";
                    
                    if ($guardian) {
                        $guardian->notify(new SpendingLimitNotification($title, "Your dependant {$user->name} has spent more than 70% of their spending limit"));
                    }
                }
            }
        }
    }

}

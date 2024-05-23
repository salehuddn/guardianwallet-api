<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Http\Services\SpendingService;
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
            if ($result && $result['code'] === '200') {
                $user->notify(new SpendingLimitNotification($result['message']));

                $guardian = $user->guardians()->first();
                if ($guardian) {
                    $guardian->notify(new SpendingLimitNotification("Your dependant {$user->name} has spent more than 70% of their spending limit"));
                }
            }
        }
    }
}

<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\User;
use App\Models\BudgetAnalysis;
use Illuminate\Console\Command;
use App\Services\AnalyticService;
use Illuminate\Support\Facades\Log;

class InsertMonthlyAnalysis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert:monthly-analysis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to insert monthly analysis for all dependants by end of the month.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $users = User::role('dependant')->get();

            foreach ($users as $user) {
                $currentMonth = Carbon::now()->format('Y-m');
                $result = AnalyticService::budgetAnalysis($user->id, $currentMonth);

                if (!is_array($result)) {
                    $this->error("Error for user ID {$user->id}: Not enough data.");
                    continue;
                }

                BudgetAnalysis::updateOrCreate(
                    ['user_id' => $user->id, 'month' => $currentMonth],
                    [
                        'income' => $result['income'],
                        'spending' => $result['spending'],
                        'limits' => $result['limits'],
                        'analysis' => $result['analysis'],
                        'recommendations' => $result['recommendations'],
                        'percentages' => $result['percentages'],
                    ]
                );

                $this->info("Monthly analysis for user ID {$user->id} inserted/updated successfully.");
            }

            $this->info('Monthly analysis for all dependants inserted/updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error in inserting monthly analysis: ' . $e->getMessage());
            $this->error('Failed to insert/update monthly analysis. Check logs for details.');
        }

    }
}

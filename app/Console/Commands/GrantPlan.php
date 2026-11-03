<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PlanService;
use Illuminate\Console\Command;

/** Gives an account free paid time, e.g. for launch partners or a refund swap. */
class GrantPlan extends Command
{
    protected $signature = 'plans:grant {mobile : The account phone number} {days=30 : How many days to add}';

    protected $description = 'Give a patient or pharmacy account free days on its paid plan';

    public function handle(PlanService $plans): int
    {
        $user = User::where('mobile_number', $this->argument('mobile'))->first();
        $plan = $user ? $plans->planFor($user) : null;

        if (!$plan) {
            $this->error('No patient or pharmacy account uses that number.');

            return self::FAILURE;
        }

        $sub = $plans->addTime($user, $plan, 'granted', (int) $this->argument('days'));
        $this->info("Granted {$plan} until {$sub->ends_at->toDayDateTimeString()}.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        \Log::info('TASK SCHEDULER WORKING', [
            'time' => now()->toDateTimeString()
        ]);
    })->everyMinute();

    $schedule->command('reminder:send --grace=10')
        ->everyMinute()
        ->withoutOverlapping();
}

   protected function commands()
{
    $this->load(__DIR__.'/Commands');

    require base_path('routes/console.php');
}
}
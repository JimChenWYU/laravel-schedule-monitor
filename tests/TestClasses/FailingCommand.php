<?php

namespace Spatie\ScheduleMonitor\Tests\TestClasses;

use Exception;
use Illuminate\Console\Command;

class FailingCommand extends Command
{
    /** @var bool */
    public static $executed = false;

    public $signature = 'failing-command';

    public function handle()
    {
        throw new Exception('failing');
    }
}

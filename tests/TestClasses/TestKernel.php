<?php

namespace Spatie\ScheduleMonitor\Tests\TestClasses;

use Closure;
use Illuminate\Console\Scheduling\Schedule;
use Orchestra\Testbench\Console\Kernel;

class TestKernel extends Kernel
{
    /** @var array */
    protected static $registeredScheduleCommands = [];

    public function commands()
    {
        return [
            FailingCommand::class,
        ];
    }

    public function schedule(Schedule $schedule)
    {
        collect(static::$registeredScheduleCommands)->each(
            function (Closure $closure) use ($schedule) {
                return $closure($schedule);
            }
        );
    }

    public static function registerScheduledTasks(Closure $closure)
    {
        static::$registeredScheduleCommands[] = $closure;
    }

    public static function replaceScheduledTasks(Closure $closure)
    {
        static::clearScheduledCommands();
        static::$registeredScheduleCommands[] = $closure;
    }

    public static function clearScheduledCommands()
    {
        static::$registeredScheduleCommands = [];
    }
}

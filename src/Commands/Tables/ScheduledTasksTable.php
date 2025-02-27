<?php

namespace Spatie\ScheduleMonitor\Commands\Tables;

use Illuminate\Console\Command;

abstract class ScheduledTasksTable
{
    /** @var Command */
    protected $command;

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    abstract public function render(): void;
}

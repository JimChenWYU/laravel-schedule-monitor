<?php

namespace Spatie\ScheduleMonitor\Commands\Tables;

use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTasks;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;

class MonitoredTasksTable extends ScheduledTasksTable
{
    public function render(): void
    {
        $this->command->line('');
        $this->command->line('Monitored tasks');
        $this->command->line('---------------');

        $tasks = ScheduledTasks::createForSchedule()
            ->uniqueTasks()
            ->filter(function (Task $task) {
                return $task->isBeingMonitored();
            });

        if ($tasks->isEmpty()) {
            $this->command->line('');
            $this->command->warn('There currently are no tasks being monitored!');

            return;
        }

        $headers = [
            'Name',
            'Type',
            'Frequency',
            'Last started at',
            'Last finished at',
            'Last failed at',
            'Next run date',
            'Grace time',
        ];

        $dateFormat = config('schedule-monitor.date_format');

        $rows = $tasks->map(function (Task $task) use ($dateFormat) {
            $row = [
                'name' => $task->name(),
                'type' => ucfirst($task->type()),
                'cron_expression' => $task->humanReadableCron(),
                'started_at' => optional($task->lastRunStartedAt())->format($dateFormat) ?? 'Did not start yet',
                'finished_at' => $this->getLastRunFinishedAt($task),
                'failed_at' => $this->getLastRunFailedAt($task),
                'next_run' => $task->nextRunAt()->format($dateFormat),
                'grace_time' => $task->graceTimeInMinutes(),
            ];

            return $row;
        });

        $this->command->table($headers, $rows);
    }

    public function getLastRunFinishedAt(Task $task)
    {
        $dateFormat = config('schedule-monitor.date_format');

        $formattedLastRunFinishedAt = optional($task->lastRunFinishedAt())->format($dateFormat) ?? '';

        if ($task->lastRunFinishedTooLate()) {
            $formattedLastRunFinishedAt = "<bg=red>{$formattedLastRunFinishedAt}</>";
        }

        return $formattedLastRunFinishedAt;
    }

    public function getLastRunFailedAt(Task $task): string
    {
        if (! $lastRunFailedAt = $task->lastRunFailedAt()) {
            return '';
        }

        $dateFormat = config('schedule-monitor.date_format');

        $formattedLastFailedAt = $lastRunFailedAt->format($dateFormat);

        if ($task->lastRunFailed()) {
            $formattedLastFailedAt = "<bg=red>{$formattedLastFailedAt}</>";
        }

        return $formattedLastFailedAt;
    }
}

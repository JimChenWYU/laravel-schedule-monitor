<?php

namespace Spatie\ScheduleMonitor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTasks;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;

class SyncCommand extends Command
{
    public $signature = 'schedule-monitor:sync';

    public $description = 'Sync the schedule of the app with the schedule monitor';

    public function handle()
    {
        $this->info('Start syncing schedule...' . PHP_EOL);

        $this
            ->syncScheduledTasksWithDatabase();

        $monitoredScheduledTasksCount = MonitoredScheduledTask::count();
        $this->info('');
        $this->info('All done! Now monitoring ' . $monitoredScheduledTasksCount . ' ' . Str::plural('scheduled task', $monitoredScheduledTasksCount) . '.');
        $this->info('');
        $this->info('Run `php artisan schedule-monitor:list` to see which jobs are now monitored.');
    }

    protected function syncScheduledTasksWithDatabase(): self
    {
        $this->comment('Start syncing schedule with database...');

        $monitoredScheduledTasks = ScheduledTasks::createForSchedule()
            ->uniqueTasks()
            ->map(function (Task $task) {
                return MonitoredScheduledTask::updateOrCreate(
                    ['name' => $task->name()],
                    [
                        'type' => $task->type(),
                        'cron_expression' => $task->cronExpression(),
                        'timezone' => $task->timezone(),
                        'grace_time_in_minutes' => $task->graceTimeInMinutes(),
                    ]
                );
            });

        MonitoredScheduledTask::query()
            ->whereNotIn('id', $monitoredScheduledTasks->pluck('id'))
            ->delete();

        return $this;
    }
}

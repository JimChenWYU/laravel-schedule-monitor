<?php

namespace Spatie\ScheduleMonitor\Support\ScheduledTasks;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task;

class ScheduledTasks
{
    /**
     * @var Schedule
     */
    protected $schedule;

    /**
     * @var Collection
     */
    protected $tasks;

    public static function createForSchedule()
    {
        $schedule = app(Schedule::class);

        return new static($schedule);
    }

    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;

        $this->tasks = collect($this->schedule->events())
            ->map(
                function (Event $event): Task {
                    return ScheduledTaskFactory::createForEvent($event);
                }
            );
    }

    public function uniqueTasks(): Collection
    {
        return $this->tasks
            ->filter(function (Task $task) {
                return $task->shouldMonitor();
            })
            ->reject(function (Task $task) {
                return empty($task->name());
            })
            ->unique(function (Task $task) {
                return $task->name();
            })
            ->values();
    }

    public function duplicateTasks(): Collection
    {
        $uniqueTasksIds = $this->uniqueTasks()
            ->map(function (Task $task) {
                return $task->uniqueId();
            })
            ->toArray();

        return $this->tasks
            ->filter(function (Task $task) {
                return $task->shouldMonitor();
            })
            ->reject(function (Task $task) {
                return empty($task->name());
            })
            ->reject(function (Task $task) use ($uniqueTasksIds) {
                return in_array($task->uniqueId(), $uniqueTasksIds);
            })
            ->values();
    }

    public function unmonitoredTasks(): Collection
    {
        return $this->tasks->reject(function (Task $task) {
            return $task->shouldMonitor();
        });
    }

    public function unnamedTasks(): Collection
    {
        return $this->tasks
            ->filter(function (Task $task) {
                return empty($task->name());
            })
            ->values();
    }
}

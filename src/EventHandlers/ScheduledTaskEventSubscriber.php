<?php

namespace Spatie\ScheduleMonitor\EventHandlers;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Contracts\Events\Dispatcher;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;

class ScheduledTaskEventSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            ScheduledTaskStarting::class,
            function (ScheduledTaskStarting $event) {
                return optional(MonitoredScheduledTask::findForTask($event->task))->markAsStarting($event);
            }
        );

        $events->listen(
            ScheduledTaskFinished::class,
            function (ScheduledTaskFinished $event) {
                return optional(MonitoredScheduledTask::findForTask($event->task))->markAsFinished($event);
            }
        );

        if (class_exists('Illuminate\Console\Events\ScheduledTaskFailed')) {
            $events->listen(
                ScheduledTaskFailed::class,
                function (ScheduledTaskFailed $event) {
                    return optional(MonitoredScheduledTask::findForTask($event->task))->markAsFailed($event);
                }
            );
        }

        $events->listen(
            ScheduledTaskSkipped::class,
            function (ScheduledTaskSkipped $event) {
                return optional(MonitoredScheduledTask::findForTask($event->task))->markAsSkipped($event);
            }
        );
    }
}

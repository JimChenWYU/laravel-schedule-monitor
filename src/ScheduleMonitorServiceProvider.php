<?php

namespace Spatie\ScheduleMonitor;

use Illuminate\Console\Events\ArtisanStarting;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\Event as SchedulerEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\ScheduleMonitor\Commands\CleanLogCommand;
use Spatie\ScheduleMonitor\Commands\ListCommand;
use Spatie\ScheduleMonitor\Commands\SyncCommand;
use Spatie\ScheduleMonitor\EventHandlers\BackgroundCommandListener;
use Spatie\ScheduleMonitor\EventHandlers\ScheduledTaskEventSubscriber;
use Spatie\ScheduleMonitor\Polyfill\ScheduledTaskFailed;
use Spatie\ScheduleMonitor\Polyfill\ScheduleRunCommand;

class ScheduleMonitorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this
            ->registerPublishables()
            ->registerCommands()
            ->configureOhDearApi()
            ->registerEventHandlers()
            ->registerSchedulerEventMacros();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/schedule-monitor.php', 'schedule-monitor');
    }

    protected function registerPublishables(): self
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/schedule-monitor.php' => config_path('schedule-monitor.php'),
            ], 'config');

            if (! class_exists('CreateScheduleMonitorTables')) {
                $this->publishes([
                    __DIR__ . '/../database/migrations/create_schedule_monitor_tables.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_schedule_monitor_tables.php'),
                ], 'migrations');
            }
        }

        return $this;
    }

    protected function registerCommands(): self
    {
        $this->commands([
            CleanLogCommand::class,
            ListCommand::class,
            SyncCommand::class,
        ]);

        if ($this->shouldPolyfill()) {
            class_alias(
                ScheduledTaskFailed::class,
                'Illuminate\Console\Events\ScheduledTaskFailed');
            $this->app['events']->listen(ArtisanStarting::class, function () {
                $this->commands([
                    ScheduleRunCommand::class,
                ]);
            });
        }

        return $this;
    }

    protected function shouldPolyfill(): bool
    {
        return !class_exists('Illuminate\Console\Events\ScheduledTaskFailed');
    }

    protected function configureOhDearApi(): self
    {
        // remove OhDear
        return $this;
    }

    protected function registerEventHandlers(): self
    {
        Event::subscribe(ScheduledTaskEventSubscriber::class);
        Event::listen(CommandStarting::class, BackgroundCommandListener::class);

        return $this;
    }

    protected function registerSchedulerEventMacros(): self
    {
        SchedulerEvent::macro('monitorName', function (string $monitorName) {
            $this->monitorName = $monitorName;

            return $this;
        });

        SchedulerEvent::macro('graceTimeInMinutes', function (int $graceTimeInMinutes) {
            $this->graceTimeInMinutes = $graceTimeInMinutes;

            return $this;
        });

        SchedulerEvent::macro('doNotMonitor', function () {
            $this->doNotMonitor = true;

            return $this;
        });

        return $this;
    }
}

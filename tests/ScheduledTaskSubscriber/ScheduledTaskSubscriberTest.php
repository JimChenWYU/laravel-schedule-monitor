<?php

namespace Spatie\ScheduleMonitor\Tests\ScheduledTaskSubscriber;

use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Bus;
use Spatie\ScheduleMonitor\Commands\SyncCommand;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;
use Spatie\ScheduleMonitor\Tests\TestCase;
use Spatie\ScheduleMonitor\Tests\TestClasses\FailingCommand;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestKernel;
use Spatie\TestTime\TestTime;

class ScheduledTaskSubscriberTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        TestKernel::registerScheduledTasks(function (Schedule $schedule) {
            $schedule->call(function () {
                return 1 + 1;
            })->everyMinute()->monitorName('dummy-task');
        });
    }

    /** @test */
    public function it_will_fire_a_job_and_create_a_log_item_when_a_monitored_scheduled_task_finished()
    {
        $this->artisan(SyncCommand::class)->assertExitCode(0);
        $this->artisan('schedule:run')->assertExitCode(0);

        $logTypes = MonitoredScheduledTask::findByName('dummy-task')->logItems->pluck('type')->toArray();

        $this->assertEquals([
            MonitoredScheduledTaskLogItem::TYPE_FINISHED,
            MonitoredScheduledTaskLogItem::TYPE_STARTING,
        ], $logTypes);
    }

    /** @test */
    public function it_will_log_skipped_scheduled_tasks()
    {
        TestKernel::replaceScheduledTasks(function (Schedule $schedule) {
            $schedule
                ->call(function () {
                    return TestTime::addSecond();
                })
                ->everyMinute()->skip(function () {
                    return true;
                })
                ->monitorName('dummy-task');
        });
        $this->artisan(SyncCommand::class)->assertExitCode(0);

        $this->artisan('schedule:run')->assertExitCode(0);

        $logTypes = MonitoredScheduledTask::findByName('dummy-task')->logItems->pluck('type')->toArray();

        $this->assertEquals([
            MonitoredScheduledTaskLogItem::TYPE_SKIPPED,
        ], $logTypes);
    }

    /** @test */
    public function it_will_log_failures_of_scheduled_tasks()
    {
        TestKernel::replaceScheduledTasks(function (Schedule $schedule) {
            $schedule
                ->call(function () {
                    throw new Exception("exception");
                })
                ->everyMinute()
                ->monitorName('failing-task');
        });

        $this->artisan(SyncCommand::class)->assertExitCode(0);
        $this->artisan('schedule:run')->assertExitCode(0);

        $logTypes = MonitoredScheduledTask::findByName('failing-task')
            ->logItems
            ->pluck('type')
            ->toArray();

        $this->assertEquals([
            MonitoredScheduledTaskLogItem::TYPE_FAILED,
            MonitoredScheduledTaskLogItem::TYPE_STARTING,
        ], $logTypes);
    }

    /** @test */
    public function it_will_mark_a_task_as_failed_when_it_throws_an_exception()
    {
        TestKernel::replaceScheduledTasks(function (Schedule $schedule) {
            $schedule->command(FailingCommand::class)->everyMinute();
        });

        $this->artisan(SyncCommand::class)->assertExitCode(0);
        $this->artisan('schedule:run')->assertExitCode(0);

        $logTypes = MonitoredScheduledTask::findByName('failing-command')
            ->logItems
            ->pluck('type')
            ->values()
            ->toArray();

        $this->assertEquals([
            MonitoredScheduledTaskLogItem::TYPE_FAILED,
            MonitoredScheduledTaskLogItem::TYPE_STARTING,
        ], $logTypes);
    }

    /** @test */
    public function it_will_not_fire_a_job_when_a_scheduled_task_finished_that_is_not_monitored()
    {
        // running the schedule
        $this->artisan('schedule:run')->assertExitCode(0);
    }

    /** @test */
    public function it_can_use_a_specific_queue()
    {
        Bus::fake();

        $this->artisan(SyncCommand::class)->assertExitCode(0);
        $this->artisan('schedule:run')->assertExitCode(0);
    }

    /** @test */
    public function it_works_correctly_when_a_task_is_run_in_the_background()
    {
        Bus::fake();

        TestKernel::registerScheduledTasks(function (Schedule $schedule) {
            $schedule->call(function () {
                return 1 + 1;
            })->everyMinute()->runInBackground();
        });

        $this->artisan(SyncCommand::class)->assertExitCode(0);
        $this->artisan('schedule:run')->assertExitCode(0);
    }
}

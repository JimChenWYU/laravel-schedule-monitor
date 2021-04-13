<?php

namespace Spatie\ScheduleMonitor\Tests;

use CreateScheduleMonitorTables;
use Illuminate\Contracts\Console\Kernel;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\ScheduleMonitor\ScheduleMonitorServiceProvider;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestKernel;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        TestKernel::clearScheduledCommands();

        $this->withFactories(__DIR__.'/database/factories');
    }

    protected function getPackageProviders($app)
    {
        return [
            ScheduleMonitorServiceProvider::class,
        ];
    }

    protected function resolveApplicationConsoleKernel($app)
    {
        $app->singleton(Kernel::class, TestKernel::class);
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        include_once __DIR__ . '/../database/migrations/create_schedule_monitor_tables.php.stub';
        (new CreateScheduleMonitorTables())->up();
    }
}

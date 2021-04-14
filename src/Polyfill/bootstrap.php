<?php

namespace Illuminate\Console\Events;

use Illuminate\Console\Scheduling\Event;
use Throwable;

if (! class_exists('Illuminate\Console\Events\ScheduledTaskFailed')) {
    class ScheduledTaskFailed
    {
        /**
         * The scheduled event that failed.
         *
         * @var \Illuminate\Console\Scheduling\Event
         */
        public $task;

        /**
         * The exception that was thrown.
         *
         * @var \Throwable
         */
        public $exception;

        /**
         * Create a new event instance.
         *
         * @param \Illuminate\Console\Scheduling\Event $task
         * @param \Throwable $exception
         */
        public function __construct(Event $task, Throwable $exception)
        {
            $this->task = $task;
            $this->exception = $exception;
        }
    }
}

// If exists, nothing to do.

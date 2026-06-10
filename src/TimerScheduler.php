<?php

namespace Nexph\Scheduler;

use Nexph\Lifecycle\TaskOwner;

class TimerScheduler extends SchedulerRuntime
{
    public function schedule(int $delay, callable $callback): void
    {
        $this->delayed((float) $delay, fn(TaskOwner $ctx) => $callback());
        $this->start();
    }

    public function scheduleRepeating(int $interval, callable $callback): void
    {
        $this->interval((float) $interval, fn(TaskOwner $ctx) => $callback());
        $this->start();
    }
}

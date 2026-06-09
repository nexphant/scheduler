<?php

namespace Nexph\Scheduler;

use Nexph\Runtime\Runtime;

class TimerScheduler
{
    private array $tasks = [];

    public function schedule(int $delay, callable $callback): void
    {
        $timerId = $this->createTimer($delay, function() use ($callback) {
            $this->spawn(fn() => $callback());
        });
        $this->tasks[] = $timerId;
    }

    public function scheduleRepeating(int $interval, callable $callback): void
    {
        $this->createRepeatingTimer($interval, function() use ($callback) {
            $this->spawn(fn() => $callback());
        });
    }

    private function createTimer(int $delay, callable $callback): mixed
    {
        if (Runtime::available()) {
            return Runtime::timer((float) $delay, $callback);
        }
        return null;
    }

    private function createRepeatingTimer(int $interval, callable $callback): mixed
    {
        if (Runtime::available()) {
            return Runtime::timer((float) $interval, $callback, true);
        }
        return null;
    }

    private function spawn(callable $callback): void
    {
        if (Runtime::available()) {
            Runtime::spawn($callback);
            return;
        }
        $callback();
    }
}

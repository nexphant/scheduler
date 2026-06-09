<?php

namespace Nexph\Scheduler;

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
        return null;
    }

    private function createRepeatingTimer(int $interval, callable $callback): mixed
    {
        return null;
    }

    private function spawn(callable $callback): void
    {
    }
}

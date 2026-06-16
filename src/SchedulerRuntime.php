<?php

namespace nexphant\Scheduler;

use nexphant\Runtime\Runtime;
use nexphant\Lifecycle\TaskOwner;

class SchedulerRuntime
{
    private array $tasks = [];
    private int $nextId = 1;
    private bool $running = false;
    private ?int $lastTaskId = null;

    public function everySecond(callable $fn): self
    {
        return $this->interval(1.0, $fn);
    }

    public function everyMinute(callable $fn): self
    {
        return $this->interval(60.0, $fn);
    }

    public function interval(float $seconds, callable $fn): self
    {
        $id = $this->nextId++;
        $this->lastTaskId = $id;
        $this->tasks[$id] = [
            'id' => $id,
            'type' => 'interval',
            'seconds' => $seconds,
            'fn' => $fn,
            'next' => microtime(true) + $seconds,
            'running' => false,
            'overlap' => true,
            'cancelled' => false,
        ];
        if ($this->running) {
            $this->scheduleTask($this->tasks[$id]);
        }
        return $this;
    }

    public function delayed(float $seconds, callable $fn): self
    {
        $id = $this->nextId++;
        $this->lastTaskId = $id;
        $this->tasks[$id] = [
            'id' => $id,
            'type' => 'once',
            'seconds' => $seconds,
            'fn' => $fn,
            'next' => microtime(true) + $seconds,
            'running' => false,
            'overlap' => true,
            'cancelled' => false,
        ];
        if ($this->running) {
            $this->scheduleTask($this->tasks[$id]);
        }
        return $this;
    }

    public function cron(string $expression, callable $fn): self
    {
        $id = $this->nextId++;
        $this->lastTaskId = $id;
        $this->tasks[$id] = [
            'id' => $id,
            'type' => 'cron',
            'expression' => $expression,
            'fn' => $fn,
            'next' => $this->nextCron($expression),
            'running' => false,
            'overlap' => true,
            'cancelled' => false,
        ];
        if ($this->running) {
            $this->scheduleTask($this->tasks[$id]);
        }
        return $this;
    }

    public function withoutOverlapping(): self
    {
        if ($this->lastTaskId !== null && isset($this->tasks[$this->lastTaskId])) {
            $this->tasks[$this->lastTaskId]['overlap'] = false;
        }
        return $this;
    }

    public function cancel(int $taskId): void
    {
        if (isset($this->tasks[$taskId])) {
            $this->tasks[$taskId]['cancelled'] = true;
        }
    }

    public function start(): void
    {
        if ($this->running) {
            return;
        }

        $this->running = true;
        
        foreach ($this->tasks as &$task) {
            $this->scheduleTask($task);
        }
    }

    private function scheduleTask(array &$task): void
    {
        if ($task['cancelled']) {
            return;
        }

        $delay = max(0, $task['next'] - microtime(true));
        
        Runtime::timer($delay, function() use (&$task) {
            if (!$this->running || $task['cancelled']) {
                return;
            }

            if (!$task['overlap'] && $task['running']) {
                $task['next'] = microtime(true) + ($task['seconds'] ?? 60);
                $this->scheduleTask($task);
                return;
            }
            
            $task['running'] = true;
            $ctx = new TaskOwner($task);
            try {
                $task['fn']($ctx);
            } finally {
                $ctx->cancel();
                $ctx->close();
                $task['running'] = false;
            }
            
            if ($task['type'] === 'interval') {
                $task['next'] = microtime(true) + $task['seconds'];
                $this->scheduleTask($task);
            } elseif ($task['type'] === 'cron') {
                $task['next'] = $this->nextCron($task['expression']);
                $this->scheduleTask($task);
            } else {
                $task['cancelled'] = true;
            }
        }, false);
    }

    public function stop(): void
    {
        $this->running = false;
    }

    private function nextCron(string $expression): float
    {
        return microtime(true) + 60;
    }
}

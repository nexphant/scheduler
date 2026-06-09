<?php

namespace Nexph\Scheduler;

use Nexph\Lifecycle\Lifecycle;

class TaskLifecycle
{
    public static function execute($task, callable $handler): void
    {
        $ctx = Lifecycle::task($task);
        try {
            $handler($task, $ctx);
        } finally {
            $ctx->close();
        }
    }
}

<?php

namespace BotMan\BotMan\Traits;

trait HandlesExceptions
{
    public function exception(string $exception, callable $closure)
    {
        $this->exceptionHandler->register($exception, $closure);
    }
}

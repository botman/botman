<?php

namespace BotMan\BotMan\Traits;

use BotMan\BotMan\Interfaces\ExceptionHandlerInterface;

trait HandlesExceptions
{
    /**
     * Register a custom exception handler.
     *
     * @param string $exception
     * @param callable $closure
     */
    public function exception(string $exception, callable $closure)
    {
        $this->exceptionHandler->register($exception, $closure);
    }

    /**
     * @param ExceptionHandlerInterface $exceptionHandler
     */
    public function setExceptionHandler(ExceptionHandlerInterface $exceptionHandler)
    {
        $this->exceptionHandler = $exceptionHandler;
    }
}

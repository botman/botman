<?php

namespace BotMan\BotMan\Interfaces;

interface TranslatableInterface
{
    /**
     * @param callable $callable
     */
    public function translate(callable $callable);

}

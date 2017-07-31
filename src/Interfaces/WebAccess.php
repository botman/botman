<?php

namespace BotMan\BotMan\Interfaces;

interface WebAccess
{
    /**
     * Get the instance as a web accessible array.
     * This will be used within the WebDriver.
     *
     * @return array
     */
    public function toWebDriver();
}

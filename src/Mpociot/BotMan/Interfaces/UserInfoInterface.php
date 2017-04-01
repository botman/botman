<?php

namespace Mpociot\BotMan\Interfaces;

interface UserInfoInterface
{
    /**
     * Return the data for the given ky
     *
     * @return mixed
     */
    public function get($key);
}

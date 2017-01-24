<?php

namespace Mpociot\BotMan\Interfaces;

interface UserInterface
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getUsername();

    /**
     * @return string
     */
    public function getFirstName();

    /**
     * @return string
     */
    public function getLastName();
}

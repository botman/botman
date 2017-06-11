<?php

namespace BotMan\BotMan\Traits;

use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Interfaces\VerifiesService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait VerifiesServices
{
    /**
     * Verify service webhook URLs.
     *
     * @return null|Response
     */
    protected function verifyServices() : ?Response
    {
        $request = (isset($this->request)) ? $this->request : Request::createFromGlobals();
        foreach (DriverManager::getConfiguredDrivers($this->config) as $driver) {
            if ($driver instanceof VerifiesService) {
                return $driver->verifyRequest($request);
            }
        }
    }
}

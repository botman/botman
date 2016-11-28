<?php

namespace Mpociot\BotMan\Drivers;

use Illuminate\Support\Collection;
use Mpociot\BotMan\Interfaces\DriverInterface;
use Mpociot\BotMan\Interfaces\HttpInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class Driver implements DriverInterface
{
    /** @var HttpInterface */
    protected $http;

    /** @var Collection */
    protected $config;

    /**
     * Driver constructor.
     * @param Request $request
     * @param array $config
     * @param HttpInterface $http
     */
    final public function __construct(Request $request, array $config, HttpInterface $http)
    {
        $this->http = $http;
        $this->config = collect($config);
        $this->buildPayload($request);
    }
}

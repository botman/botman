<?php

namespace Mpociot\SlackBot\Drivers;

use Illuminate\Support\Collection;
use Mpociot\SlackBot\Interfaces\DriverInterface;
use Mpociot\SlackBot\Interfaces\HttpInterface;
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
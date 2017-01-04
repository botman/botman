<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\Message;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Interfaces\HttpInterface;
use Symfony\Component\HttpFoundation\Request;
use Mpociot\BotMan\Interfaces\DriverInterface;

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
        $this->config = Collection::make($config);
        $this->buildPayload($request);
    }

    /**
     * @param Message $matchingMessage
     * @return void
     */
    public function types(Message $matchingMessage)
    {
        // Do nothing
    }

    /**
     * @param Request $request
     * @return void
     */
    abstract public function buildPayload(Request $request);
}

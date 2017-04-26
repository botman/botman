<?php

namespace Mpociot\BotMan\Middleware;

use Closure;
use Mpociot\BotMan\BotMan;
use Mpociot\Pipeline\Pipeline;
use Mpociot\BotMan\Interfaces\MiddlewareInterface;

class MiddlewareManager
{
    /** @var MiddlewareInterface[] */
    protected $received = [];
    /** @var MiddlewareInterface[] */
    protected $heard = [];
    /** @var MiddlewareInterface[] */
    protected $sending = [];
    /** @var BotMan */
    protected $botman;

    public function __construct(BotMan $botman)
    {
        $this->botman = $botman;
    }

    /**
     * @param MiddlewareInterface[] ...$middleware
     * @return $this
     */
    public function received(MiddlewareInterface ...$middleware)
    {
        if (empty($middleware)) {
            return $this->received;
        }
        $this->received = array_merge($this->received, $middleware);

        return $this;
    }

    /**
     * @param MiddlewareInterface[] $middleware
     * @return MiddlewareInterface[]|$this
     */
    public function heard(MiddlewareInterface ...$middleware)
    {
        if (empty($middleware)) {
            return $this->heard;
        }
        $this->heard = array_merge($this->heard, $middleware);

        return $this;
    }

    /**
     * @param MiddlewareInterface[] $middleware
     * @return $this
     */
    public function sending(MiddlewareInterface ...$middleware)
    {
        if (empty($middleware)) {
            return $this->sending;
        }
        $this->sending = array_merge($this->sending, $middleware);

        return $this;
    }

    /**
     * @param string $method
     * @param mixed $payload
     * @param MiddlewareInterface[] $additionalMiddleware
     * @param Closure|null $destination
     * @return mixed
     */
    public function applyMiddleware($method, $payload, array $additionalMiddleware = [], Closure $destination = null)
    {
        $destination = is_null($destination) ? function ($payload) {
            return $payload;
        }
            : $destination;

        $middleware = $this->$method + $additionalMiddleware;

        return (new Pipeline())
            ->via($method)
            ->send($payload)
            ->with($this->botman)
            ->through($middleware)
            ->then($destination);
    }
}
<?php

namespace Mpociot\BotMan;

use Illuminate\Support\Collection;
use Mpociot\BotMan\Interfaces\MiddlewareInterface;

class Command
{
    /** @var string */
    protected $pattern;

    /** @var Closure|string */
    protected $callback;

    /** @var string */
    protected $in;

    /** @var string */
    protected $driver;

    /** @var array */
    protected $middleware = [];

    /**
     * Command constructor.
     * @param string $pattern
     * @param Closure|string $callback
     * @param string|null $in
     * @param string|null $driver
     */
    public function __construct($pattern, $callback, $in = null, $driver = null)
    {
        $this->pattern = $pattern;
        $this->callback = $callback;
        $this->driver = $driver;
        $this->in = $in;
    }

    /**
     * Apply possible group attributes.
     *
     * @param  array  $attributes
     */
    public function applyGroupAttributes(array $attributes)
    {
        if (isset($attributes['middleware'])) {
            $this->middleware($attributes['middleware']);
        }

        if (isset($attributes['driver'])) {
            $this->driver($attributes['driver']);
        }
    }

    /**
     * @param $in
     * @return $this
     */
    public function in($in)
    {
        $this->in = $in;

        return $this;
    }

    /**
     * @param $driver
     * @return $this
     */
    public function driver($driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @param array|MiddlewareInterface $middleware
     * @return $this
     */
    public function middleware($middleware)
    {
        if (! is_array($middleware)) {
            $middleware = [$middleware];
        }

        $this->middleware = Collection::make($middleware)->filter(function ($item) {
            return $item instanceof MiddlewareInterface;
        })->merge($this->middleware)->toArray();

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'pattern' => $this->pattern,
            'callback' => $this->callback,
            'driver' => $this->driver,
            'middleware' => $this->middleware,
            'in' => $this->in,
        ];
    }
}

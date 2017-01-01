<?php

namespace Mpociot\BotMan;

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
     * @return array
     */
    public function toArray()
    {
        return [
            'pattern' => $this->pattern,
            'callback' => $this->callback,
            'driver' => $this->driver,
            'in' => $this->in,
        ];
    }

}

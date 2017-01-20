<?php

namespace Mpociot\BotMan\Messages;

class Message
{
    /** @var string */
    protected $message;

    /** @var string */
    protected $image;

    /** @var string */
    protected $video;

    /**
     * Message constructor.
     * @param string $message
     * @param string $image
     */
    public function __construct($message = null, $image = null)
    {
        $this->message = $message;
        $this->image = $image;
    }

    /**
     * @param string $message
     * @param string $image
     * @return Message
     */
    public static function create($message = null, $image = null)
    {
        return new self($message, $image);
    }

    /**
     * Shorter version of setter/getter methods
     * $Message->video($video);
     * will set $this->video object and
     * $Message->getVideo();
     * will return $this->video object.
     *
     * @param string $methodName
     * @param array $arguments
     * @return mixed
     */
    public function __call($methodName, $arguments)
    {
        // if get<Method> is requested
        if (strpos($methodName, 'get') !== false) {
            $normilizedMethodName = strtolower(str_replace('get', '', $methodName));
            // check if object is set and return
            if (isset($this->{$normilizedMethodName})) {
                return $this->{$normilizedMethodName};
            }
        } else {
            $this->{$methodName} = isset($arguments[0]) ? $arguments[0] : $arguments;

            return $this;
        }
    }
}

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

    /** @var string */
    protected $filePath;

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
     * @param string $message
     * @return $this
     */
    public function message($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @param string $image
     * @return $this
     */
    public function image($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @param string $video
     * @return $this
     */
    public function video($video)
    {
        $this->video = $video;

        return $this;
    }

    /**
     * @param string $filePath
     * @return $this
     */
    public function filePath($filePath)
    {
        $this->filePath = $filePath;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return string
     */
    public function getVideo()
    {
        return $this->video;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }
}

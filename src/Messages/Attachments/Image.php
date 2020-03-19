<?php

namespace BotMan\BotMan\Messages\Attachments;

use BotMan\BotMan\Interfaces\TranslatableInterface;

class Image extends Attachment implements TranslatableInterface
{
    /**
     * Pattern that messages use to identify image uploads.
     */
    const PATTERN = '%%%_IMAGE_%%%';

    /** @var string */
    protected $url;

    /** @var string */
    protected $title;

    /** @var bool */
    protected $isTranslated;

    /**
     * Video constructor.
     * @param string $url
     * @param mixed $payload
     */
    public function __construct($url, $payload = null)
    {
        parent::__construct($payload);
        $this->url = $url;
    }

    /**
     * @param $url
     * @return Image
     */
    public static function url($url)
    {
        return new self($url);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param $title
     * @return Image
     */
    public function title($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get the instance as a web accessible array.
     * This will be used within the WebDriver.
     *
     * @return array
     */
    public function toWebDriver()
    {
        return [
            'type' => 'image',
            'url' => $this->url,
            'title' => $this->title,
        ];
    }

    /**
     * @param callable $callable
     */
    public function translate(callable $callable)
    {
        if ($this->isTranslated) {
            return;
        }
        $this->title = $callable($this->title);
        $this->isTranslated = true;
    }
}

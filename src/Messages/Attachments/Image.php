<?php

namespace BotMan\BotMan\Messages\Attachments;

class Image extends Attachment
{
    /**
     * Pattern that messages use to identify image uploads.
     */
    const PATTERN = '%%%_IMAGE_%%%';

    /** @var string */
    protected $url;

    /** @var string */
    protected $title;

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
}

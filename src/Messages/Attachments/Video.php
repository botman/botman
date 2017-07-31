<?php

namespace BotMan\BotMan\Messages\Attachments;

class Video extends Attachment
{
    /**
     * Pattern that messages use to identify video uploads.
     */
    const PATTERN = '%%%_VIDEO_%%%';

    /** @var string */
    protected $url;

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
     * @return Video
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
     * Get the instance as a web accessible array.
     * This will be used within the WebDriver.
     *
     * @return array
     */
    public function toWebDriver()
    {
        return [
            'type' => 'video',
            'url' => $this->url,
        ];
    }
}

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
}

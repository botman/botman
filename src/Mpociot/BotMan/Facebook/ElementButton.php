<?php

namespace Mpociot\BotMan\Facebook;

class ElementButton
{
    /** @var string */
    protected $title;

    /** @var string */
    protected $type = 'web_url';

    /** @var string */
    protected $url;

    /** @var string */
    protected $payload;

    const TYPE_ACCOUNT_LINK = 'account_link';

    /**
     * @param string $title
     * @return static
     */
    public static function create($title)
    {
        return new static($title);
    }

    /**
     * @param string $title
     */
    public function __construct($title)
    {
        $this->title = $title;
    }

    /**
     * Set the button URL.
     * @param string $url
     * @return $this
     */
    public function url($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Set the button type.
     * @param string $type
     * @return $this
     */
    public function type($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param $payload
     * @return $this
     */
    public function payload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $buttonArray = [
            'type' => $this->type,
        ];

        if ($this->type !== self::TYPE_ACCOUNT_LINK) {
            $buttonArray['title'] = $this->title;
        }

        if ($this->type === 'postback') {
            $buttonArray['payload'] = $this->payload;
        } else {
            $buttonArray['url'] = $this->url;
        }

        return $buttonArray;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}

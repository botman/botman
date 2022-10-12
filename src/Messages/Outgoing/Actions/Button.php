<?php

namespace BotMan\BotMan\Messages\Outgoing\Actions;

use BotMan\BotMan\Interfaces\QuestionActionInterface;
use JsonSerializable;

class Button implements JsonSerializable, QuestionActionInterface
{
    /** @var string */
    protected $text;

    /** @var string */
    protected $value;

    /** @var string */
    protected $name;

    /** @var array */
    protected $additional = [];

    /** @var string */
    protected $imageUrl;
    
    /** @var url */
    protected $url;

    /**
     * @param string $text
     *
     * @return static
     */
    public static function create($text)
    {
        return new static($text);
    }

    /**
     * @param string $text
     */
    public function __construct($text)
    {
        $this->text = $text;
    }

    /**
     * Set the button value.
     *
     * @param string $value
     * @return $this
     */
    public function value($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Set the button name (defaults to button text).
     *
     * @param string $name
     * @return $this
     */
    public function name($name)
    {
        $this->name = $name;

        return $this;
    }
    
    /**
     * Set the button url (telegram only).
     *
     * @param string $url
     * @return $this
     */
    public function url($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Set the button additional parameters to pass to the service.
     *
     * @param array $additional
     * @return $this
     */
    public function additionalParameters(array $additional)
    {
        $this->additional = $additional;

        return $this;
    }

    /**
     * Set the button image (Facebook only).
     *
     * @param string $imageUrl
     * @return $this
     */
    public function image($imageUrl)
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'name' => isset($this->name) ? $this->name : $this->text,
            'text' => $this->text,
            'image_url' => $this->imageUrl,
            'url' => $this->url,
            'type' => 'button',
            'value' => $this->value,
            'additional' => $this->additional,
        ];
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}

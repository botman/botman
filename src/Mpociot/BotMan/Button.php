<?php

namespace Mpociot\BotMan;

use JsonSerializable;

class Button implements JsonSerializable
{
    /** @var string */
    protected $text;

    /** @var string */
    protected $value;

    /** @var string */
    protected $name;

    /** @var string */
    protected $image_url;

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
     * Shorter version of setter method
     * @param string $methodName
     * @param array #arguments
     * @return Object
     */
    public function __call($methodName, $arguments)
    {
        $this->{$methodName} = isset($arguments[0]) ? $arguments[0] : $arguments;
        return $this;
    }

    /**
     * Set the button image (Facebook only).
     *
     * @param string $image_url
     * @return $this
     */
    public function image($image_url)
    {
        $this->image_url = $image_url;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        if (isset($this->url)) {
            return [
                "type"  => isset($this->type) ? $this->type : "web_url",
                "url"   => $this->url,
                "title" => isset($this->title) ? $this->title : $this->text,
            ];
        } else {
            return [
                'name'      => isset($this->name) ? $this->name : $this->text,
                'text'      => $this->text,
                'image_url' => $this->image_url,
                'type'      => 'button',
                'value'     => $this->value,
            ];
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}

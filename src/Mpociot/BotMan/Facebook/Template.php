<?php

namespace Mpociot\BotMan\Facebook;

use JsonSerializable;

class Template implements JsonSerializable
{
    /** @var string */
    protected $template_type = 'generic';

    /** @var string */
    protected $text;

    /** @var string */
    protected $value;

    /** @var string */
    protected $name;

    /** @var array */
    protected $elements = [];

    /**
     * @param string $template_type The PHP template type to use
     * @return static
     */
    public static function create($template_type = 'generic')
    {
        return new static($template_type);
    }

    /**
     * @param string $template_type The PHP template type to use
     */
    public function __construct($template_type)
    {
        $this->template_type = $template_type;
    }

    /**
     * @param string $image_url
     * @return $this
     */
    public function image($image_url)
    {
        $this->image_url = $image_url;

        return $this;
    }

    /**
     * @param Element $element
     * @return $this
     */
    public function addElement(Element $element)
    {
        $this->elements[] = $element->toArray();

        return $this;
    }

    /**
     * @param array $elements
     * @return $this
     */
    public function addElements(array $elements)
    {
        foreach ($elements as $element) {
            if ($element instanceof Element) {
                $this->elements[] = $element->toArray();
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => $this->template_type,
                    'elements' => $this->elements,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}

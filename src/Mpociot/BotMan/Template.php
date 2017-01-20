<?php

namespace Mpociot\BotMan;

use JsonSerializable;

class Template implements JsonSerializable
{
    /** @var string */
    protected $text;

    /** @var string */
    protected $value;

    /** @var string */
    protected $name;

    private $webview_height_ratio = 'full';

    /**
     * @param string $text
     *
     * @return static
     */
    public static function create($template_type = 'generic')
    {
        return new static($template_type);
    }

    /**
     * @param string $template_type
     */
    public function __construct($template_type)
    {
        $this->template_type = $template_type;
    }

    /**
     * Shorter version of setter method.
     * @param string $methodName
     * @param array $arguments
     * @return $this
     */
    public function __call($methodName, $arguments)
    {
        $this->{$methodName} = isset($arguments[0]) ? $arguments[0] : $arguments;
        return $this;
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
     * @param Button $button
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
        if (isset($elements) && is_array($elements)) {
            foreach ($elements as $element) {
                if ($element instanceof Element) {
                    $this->elements[] = $element->toArray();
                }
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'attachment' => array(
                'type'    => 'template',
                'payload' => array(
                    'template_type' => isset($this->template_type) ? $this->template_type : 'generic',
                    'elements'      => $this->elements
                ),
            ),
        );
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}

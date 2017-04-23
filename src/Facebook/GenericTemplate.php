<?php

namespace Mpociot\BotMan\Facebook;

use JsonSerializable;

class GenericTemplate implements JsonSerializable
{
    const RATIO_HORIZONTAL = 'horizontal';
    const RATIO_SQUARE = 'square';

    /** @var array */
    private static $allowedRatios = [
        self::RATIO_HORIZONTAL,
        self::RATIO_SQUARE,
    ];

    /** @var array */
    protected $elements = [];

    /** @var string */
    protected $imageAspectRatio = self::RATIO_HORIZONTAL;

    /**
     * @return static
     */
    public static function create()
    {
        return new static;
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
     * @param string $ratio
     * @return $this
     */
    public function addImageAspectRatio($ratio)
    {
        if (in_array($ratio, self::$allowedRatios)) {
            $this->imageAspectRatio = $ratio;
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
                    'template_type' => 'generic',
                    'image_aspect_ratio' => $this->imageAspectRatio,
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

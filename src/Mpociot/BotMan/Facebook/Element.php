<?php

namespace Mpociot\BotMan\Facebook;

use JsonSerializable;

class Element implements JsonSerializable
{
    /** @var string */
    protected $title;

    /** @var string */
    protected $image_url;

    /** @var string */
    protected $item_url;

    /** @var string */
    protected $subtitle;

    /** @var object */
    protected $buttons;

    /**
     * @param $title
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
     * @param string $title
     * @return $this
     */
    public function title($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param string $subtitle
     * @return $this
     */
    public function subtitle($subtitle)
    {
        $this->subtitle = $subtitle;

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
     * @param string $item_url
     * @return $this
     */
    public function itemUrl($item_url)
    {
        $this->item_url = $item_url;

        return $this;
    }

    /**
     * @param array|ElementButton $button
     * @return $this
     */
    public function addButton($button)
    {
        $this->buttons[] = $button instanceof ElementButton ? $button->toArray() : $button;

        return $this;
    }

    /**
     * @param array $buttons
     * @return $this
     */
    public function addButtons(array $buttons)
    {
        if (isset($buttons) && is_array($buttons)) {
            foreach ($buttons as $button) {
                $this->buttons[] = $button instanceof ElementButton ? $button->toArray() : $button;
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
            'title' => $this->title,
            'image_url' => $this->image_url,
            'item_url' => $this->item_url,
            'subtitle' => $this->subtitle,
            'buttons' => $this->buttons,
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

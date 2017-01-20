<?php

namespace Mpociot\BotMan;

use JsonSerializable;

class Element implements JsonSerializable
{
    /** @var string */
    protected $title;

    /** @var string */
    protected $image_url;

    /** @var object */
    protected $buttons;

    private $webview_height_ratio = 'full';

    /**
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
    public function addButton(Button $button)
    {
        $this->buttons[] = $button->toArray();

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
                if ($button instanceof Button) {
                    $this->buttons[] = $button->toArray();
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
        return [
            'title' => isset($this->title) ? $this->title : '',
            'image_url' => isset($this->image_url) ? $this->image_url : '',
            'subtitle' => isset($this->subtitle) ? $this->subtitle : '',
            'default_action' => [
                'type' => 'web_url',
                'url' => isset($this->url) ? $this->url : '',
                'messenger_extensions' => isset($this->messenger_extensions) ? $this->messenger_extensions : 'true',
                'webview_height_ratio' => isset($this->webview_height_ratio) ? $this->webview_height_ratio : 'compact',
                'fallback_url' => isset($this->fallback_url) ? $this->fallback_url : $this->url,
            ],
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

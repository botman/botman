<?php

namespace BotMan\BotMan\Messages\Outgoing;

use BotMan\BotMan\Interfaces\QuestionActionInterface;
use BotMan\BotMan\Interfaces\TranslatableInterface;
use BotMan\BotMan\Interfaces\WebAccess;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use JsonSerializable;

class Question implements JsonSerializable, WebAccess, TranslatableInterface
{
    /** @var array */
    protected $actions;

    /** @var array */
    protected $actionInstances;

    /** @var string */
    protected $text;

    /** @var string */
    protected $callback_id;

    /** @var string */
    protected $fallback;

    /** @var bool */
    private $isTranslated;

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
        $this->actions = [];
        $this->actionInstances = [];
    }

    /**
     * Set the question fallback value.
     *
     * @param string $fallback
     * @return $this
     */
    public function fallback($fallback)
    {
        $this->fallback = $fallback;

        return $this;
    }

    /**
     * Set the callback id.
     *
     * @param string $callback_id
     * @return $this
     */
    public function callbackId($callback_id)
    {
        $this->callback_id = $callback_id;

        return $this;
    }

    public function addAction(QuestionActionInterface $action)
    {
        $this->actions[] = $action->toArray();
        $this->actionInstances[] = $action;

        return $this;
    }

    /**
     * @param \BotMan\BotMan\Messages\Outgoing\Actions\Button $button
     * @return $this
     */
    public function addButton(Button $button)
    {
        $this->actions[] = $button->toArray();
        $this->actionInstances[] = $button;

        return $this;
    }

    /**
     * @param array $buttons
     * @return $this
     */
    public function addButtons(array $buttons)
    {
        foreach ($buttons as $button) {
            $this->actions[] = $button->toArray();
            $this->actionInstances[] = $button;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'text' => $this->text,
            'fallback' => $this->fallback,
            'callback_id' => $this->callback_id,
            'actions' => $this->actions,
        ];
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return array
     */
    public function getButtons()
    {
        return $this->actions;
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
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
            'type' => (count($this->actions) > 0) ? 'actions' : 'text',
            'text' => $this->text,
            'fallback' => $this->fallback,
            'callback_id' => $this->callback_id,
            'actions' => $this->actions,
        ];
    }

    public function translate(callable $callable)
    {
        $translatedActions = [];
        foreach ($this->actionInstances as $actionInstance) {
            if ($actionInstance instanceof TranslatableInterface) {
                $actionInstance->translate($callable);
            }
            $translatedActions[] = $actionInstance->toArray();
        }
        if ($this->isTranslated) {
            return;
        }
        $this->text = $callable($this->text);
        $this->actions = $translatedActions;
        $this->isTranslated = true;
    }
}

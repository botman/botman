<?php

namespace Mpociot\BotMan\Drivers\Slack\Extensions;

use Mpociot\BotMan\Interfaces\QuestionActionInterface;

class Menu implements QuestionActionInterface
{
    const DATA_SOURCE_USERS = 'users';

    const DATA_SOURCE_CHANNELS = 'channels';

    const DATA_SOURCE_CONVERSATIONS = 'conversations';

    /** @var string */
    protected $name;

    /** @var string */
    protected $text;

    /** @var array */
    protected $options;

    /** @var array */
    protected $optionGroups;

    /** @var array */
    protected $selectedOptions;

    /** @var string */
    protected $dataSource;

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
     * Set the available menu options.
     *
     * @param array $options
     * @return $this
     */
    public function options(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Set the available menu option groups.
     *
     * @param array $optionGroups
     * @return $this
     */
    public function optionGroups(array $optionGroups)
    {
        $this->optionGroups = $optionGroups;

        return $this;
    }

    /**
     * Set the button value.
     *
     * @param array $options
     * @return $this
     */
    public function selectedOptions(array $options)
    {
        $this->selectedOptions = $options;

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
     * Populate the menu with all available users.
     *
     * @return $this
     */
    public function chooseFromUsers()
    {
        $this->dataSource = self::DATA_SOURCE_USERS;

        return $this;
    }

    /**
     * Populate the mnue with all available channels.
     *
     * @return $this
     */
    public function chooseFromChannels()
    {
        $this->dataSource = self::DATA_SOURCE_CHANNELS;

        return $this;
    }

    /**
     * Populate the menu with all available conversations.
     *
     * @return $this
     */
    public function chooseFromConversations()
    {
        $this->dataSource = self::DATA_SOURCE_CONVERSATIONS;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = [
            'name' => isset($this->name) ? $this->name : $this->text,
            'text' => $this->text,
            'type' => 'select',
        ];
        if (count($this->options) > 0) {
            $data['options'] = $this->options;
            $data['selected_options'] = $this->selectedOptions;
        } elseif (count($this->optionGroups) > 0) {
            $data['option_groups'] = $this->optionGroups;
            $data['selected_options'] = $this->selectedOptions;
        } else {
            $data['data_source'] = $this->dataSource;
        }

        return $data;
    }
}

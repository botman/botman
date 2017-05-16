<?php

namespace Mpociot\BotMan\Messages;

use Mpociot\BotMan\Command;
use Mpociot\BotMan\Message;

class MatchingMessage
{
    /** @var Command */
    protected $command;

    /** @var Message */
    protected $message;

    /** @var array */
    private $matches;

    /**
     * MatchingMessage constructor.
     * @param Command $command
     * @param Message $message
     * @param array $matches
     */
    public function __construct(Command $command, Message $message, array $matches)
    {
        $this->command = $command;
        $this->message = $message;
        $this->matches = $matches;
    }

    /**
     * @return Command
     */
    public function getCommand(): Command
    {
        return $this->command;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * @return array
     */
    public function getMatches(): array
    {
        return $this->matches;
    }
}

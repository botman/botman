<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Illuminate\Support\Collection;
use Phergie\Irc\Client\React\Client;
use Phergie\Irc\Client\React\WriteStream;
use Mpociot\BotMan\Interfaces\DriverInterface;
use Mpociot\BotMan\Messages\Message as IncomingMessage;

class IrcDriver implements DriverInterface
{
    /** @var Collection */
    protected $event;

    /** @var Client */
    protected $client;

    /** @var WriteStream */
    protected $write;

    const DRIVER_NAME = 'Irc';

    /**
     * Driver constructor.
     * @param array $config
     * @param Client $client
     */
    public function __construct(array $config, Client $client)
    {
        $this->event = Collection::make();
        $this->config = Collection::make($config);
        $this->client = $client;

        $this->client->on('irc.received', function ($message, $write, $connection, $logger) {
            $event = Collection::make($message);
            if ($event->get('command') === 'PRIVMSG') {
                $this->event = $event;
                $this->write = $write;
            }
        });
    }

    /**
     * Return the driver name.
     *
     * @return string
     */
    public function getName()
    {
        return self::DRIVER_NAME;
    }

    public function isUsingReactPHP()
    {
        return true;
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return false;
    }

    /**
     * @param  Message $message
     * @return Answer
     */
    public function getConversationAnswer(Message $message)
    {
        return Answer::create($this->event->get('text'))->setMessage($message);
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $messageText = $this->event->get('params')['text'];
        $user_id = $this->event->get('nick');
        $channel_id = $this->event->get('params')['receivers'];

        return [new Message($messageText, $user_id, $channel_id, $this->event)];
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return $this->event->has('bot_id');
    }

    /**
     * @param string|Question $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return $this
     */
    public function reply($message, $matchingMessage, $additionalParameters = [])
    {
        if ($message instanceof IncomingMessage) {
            $text = $message->getMessage();
            if (! is_null($message->getImage())) {
                //$parameters['attachments'] = json_encode([['title' => $message->getMessage(), 'image_url' => $message->getImage()]]);
            }
        } elseif ($message instanceof Question) {
        } else {
            $text = $message;
        }

        $this->write->ircPrivmsg($matchingMessage->getChannel(), $text);
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return ! is_null($this->config->get('slack_token'));
    }

    /**
     * Send a typing indicator.
     * @param Message $matchingMessage
     * @return mixed
     */
    public function types(Message $matchingMessage)
    {
    }

    /**
     * Retrieve User information.
     * @param Message $matchingMessage
     * @return User
     */
    public function getUser(Message $matchingMessage)
    {
        return new User($matchingMessage->getUser());
    }
}

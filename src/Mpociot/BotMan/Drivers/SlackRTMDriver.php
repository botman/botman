<?php

namespace Mpociot\BotMan\Drivers;

use Slack\File;
use Mpociot\BotMan\User;
use Slack\RealTimeClient;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Interfaces\DriverInterface;
use Mpociot\BotMan\Messages\Message as IncomingMessage;

class SlackRTMDriver implements DriverInterface
{
    /** @var Collection */
    protected $event;

    /** @var RealTimeClient */
    protected $client;

    /** @var string */
    protected $bot_id;

    const DRIVER_NAME = 'SlackRTM';

    /**
     * Driver constructor.
     * @param array $config
     * @param RealTimeClient $client
     */
    public function __construct(array $config, RealTimeClient $client)
    {
        $this->event = Collection::make();
        $this->config = Collection::make($config);
        $this->client = $client;

        $this->client->on('message', function ($data) {
            $this->event = Collection::make($data);
        });
    }

    /**
     * Connected event.
     */
    public function connected()
    {
        $this->client->getAuthedUser()->then(function ($user) {
            $this->bot_id = $user->getId();
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
        $messageText = $this->event->get('text');
        $user_id = $this->event->get('user');
        $channel_id = $this->event->get('channel');

        return [new Message($messageText, $user_id, $channel_id, $this->event)];
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return $this->event->has('bot_id') && $this->event->get('bot_id') !== $this->bot_id;
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return $this
     */
    public function reply($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = array_merge([
            'channel' => $matchingMessage->getChannel(),
            'as_user' => true,
        ], $additionalParameters);

        $fileToUpload = null;

        if ($message instanceof IncomingMessage) {
            $parameters['text'] = $message->getMessage();
            if (! is_null($message->getImage())) {
                $parameters['attachments'] = json_encode([['title' => $message->getMessage(), 'image_url' => $message->getImage()]]);
            }

            if (! empty($message->getFilePath()) && file_exists($message->getFilePath())) {
                $fileToUpload = (new File())
                    ->setTitle(basename($message->getFilePath()))
                    ->setPath($message->getFilePath())
                    ->setInitialComment($message->getMessage());
            }
        } elseif ($message instanceof Question) {
            $parameters['text'] = '';
            $parameters['attachments'] = json_encode([$message->toArray()]);
        } else {
            $parameters['text'] = $message;
        }

        if (empty($fileToUpload)) {
            $this->client->apiCall('chat.postMessage', $parameters, false, false);
        } else {
            $this->client->fileUpload($fileToUpload, [$matchingMessage->getChannel()]);
        }

        return $this;
    }

    /**
     * @param $message
     * @param array $additionalParameters
     * @param Message $matchingMessage
     * @return SlackRTMDriver
     */
    public function replyInThread($message, $additionalParameters, $matchingMessage)
    {
        $additionalParameters['thread_ts'] = $matchingMessage->getPayload()->get('ts');

        return $this->reply($message, $matchingMessage, $additionalParameters);
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
        $user = null;
        $this->client->getUserById($matchingMessage->getUser())->then(function ($_user) use (&$user) {
            $user = $_user;
        });
        if (! is_null($user)) {
            return new User($matchingMessage->getUser(), $user->getFirstName(), $user->getLastName(), $user->getUsername());
        }

        return new User($matchingMessage->getUser());
    }

    /**
     * Retrieve Channel information.
     * @param Message $matchingMessage
     * @return \Slack\Channel
     */
    public function getChannel(Message $matchingMessage)
    {
        return $this->client->getChannelById($matchingMessage->getChannel());
    }

    /**
     * @return RealTimeClient
     */
    public function getClient()
    {
        return $this->client;
    }
}

<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\User;
use Slack\RealTimeClient;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\BotMan;
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

        if ($this->event->get('subtype') === 'file_share') {
            $file = Collection::make($this->event->get('file'));

            if (strstr($file->get('mimetype'), 'image')) {
                $message = new Message(BotMan::IMAGE_PATTERN, $user_id, $channel_id, $this->event);
                $message->setImages([$file->get('permalink')]);
            } elseif (strstr($file->get('mimetype'), 'audio')) {
                $message = new Message(BotMan::AUDIO_PATTERN, $user_id, $channel_id, $this->event);
                $message->setAudio([$file->get('permalink')]);
            } elseif (strstr($file->get('mimetype'), 'video')) {
                $message = new Message(BotMan::VIDEO_PATTERN, $user_id, $channel_id, $this->event);
                $message->setVideos([$file->get('permalink')]);
            } else {
                $message = new Message(BotMan::ATTACHMENT_PATTERN, $user_id, $channel_id, $this->event);
                $message->setAttachments([$file->get('permalink')]);
            }

            return [$message];
        }

        return [new Message($messageText, $user_id, $channel_id, $this->event)];
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return $this->event->has('bot_id') && ! is_null($this->event->get('bot_id'));
    }

    /**
     * @param string|Question $message
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

        if ($message instanceof IncomingMessage) {
            $parameters['text'] = $message->getMessage();
            if (! is_null($message->getImage())) {
                $parameters['attachments'] = json_encode([['title' => $message->getMessage(), 'image_url' => $message->getImage()]]);
            }
        } elseif ($message instanceof Question) {
            $parameters['text'] = '';
            $parameters['attachments'] = json_encode([$message->toArray()]);
        } else {
            $parameters['text'] = $message;
        }

        $this->client->apiCall('chat.postMessage', $parameters);
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
        return new User($matchingMessage->getUser());
    }
}

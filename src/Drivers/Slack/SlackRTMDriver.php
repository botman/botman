<?php

namespace Mpociot\BotMan\Drivers\Slack;

use Slack\File;
use Mpociot\BotMan\User;
use Slack\RealTimeClient;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Illuminate\Support\Collection;
use React\Promise\PromiseInterface;
use Mpociot\BotMan\Attachments\Audio;
use Mpociot\BotMan\Attachments\Image;
use Mpociot\BotMan\Attachments\Video;
use Mpociot\BotMan\Interfaces\DriverInterface;
use Mpociot\BotMan\Attachments\File as BotManFile;
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

    protected $file;

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
     * @return bool
     */
    public function hasMatchingEvent()
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
                $message = new Message(Image::PATTERN, $user_id, $channel_id, $this->event);
                $message->setImages([$file->get('permalink')]);
            } elseif (strstr($file->get('mimetype'), 'audio')) {
                $message = new Message(Audio::PATTERN, $user_id, $channel_id, $this->event);
                $message->setAudio([$file->get('permalink')]);
            } elseif (strstr($file->get('mimetype'), 'video')) {
                $message = new Message(Video::PATTERN, $user_id, $channel_id, $this->event);
                $message->setVideos([$file->get('permalink')]);
            } else {
                $message = new Message(\Mpociot\BotMan\Attachments\File::PATTERN, $user_id, $channel_id, $this->event);
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
        return $this->event->has('bot_id') && $this->event->get('bot_id') !== $this->bot_id;
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return mixed
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = array_merge_recursive([
            'channel' => $matchingMessage->getChannel(),
            'as_user' => true,
        ], $additionalParameters);

        $this->file = null;

        if ($message instanceof IncomingMessage) {
            $parameters['text'] = $message->getText();
            $attachment = $message->getAttachment();
            if (! is_null($attachment)) {
                if ($attachment instanceof Image) {
                    $parameters['attachments'] = json_encode([
                        [
                            'title' => $message->getText(),
                            'image_url' => $attachment->getUrl(),
                        ],
                    ]);

                    // else check if is a path
                } elseif ($attachment instanceof BotManFile && file_exists($attachment->getUrl())) {
                    $this->file = (new File())
                        ->setTitle(basename($attachment->getUrl()))
                        ->setPath($attachment->getUrl())
                        ->setInitialComment($message->getText());
                }
            }
        } elseif ($message instanceof Question) {
            $parameters['text'] = '';
            $parameters['attachments'] = json_encode([$message->toArray()]);
        } else {
            $parameters['text'] = $message;
        }

        return (is_null($this->file)) ? $parameters : [$matchingMessage->getChannel()];
    }

    /**
     * @param mixed $payload
     * @return PromiseInterface
     */
    public function sendPayload($payload)
    {
        if (is_null($this->file)) {
            return $this->client->apiCall('chat.postMessage', $payload, false, false);
        }

        return $this->client->fileUpload($this->file, $payload);
    }

    /**
     * @param $message
     * @param array $additionalParameters
     * @param Message $matchingMessage
     * @return SlackRTMDriver
     */
    public function replyInThread($message, $additionalParameters, $matchingMessage)
    {
        $additionalParameters['thread_ts'] = ! empty($matchingMessage->getPayload()->get('thread_ts'))
            ? $matchingMessage->getPayload()->get('thread_ts')
            : $matchingMessage->getPayload()->get('ts');

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
        $channel = null;
        $this->getChannelGroupOrDM($matchingMessage)->then(function ($_channel) use (&$channel) {
            $channel = $_channel;
        });

        if (! is_null($channel)) {
            $this->client->setAsTyping($channel, false);
        }
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
            return new User($matchingMessage->getUser(), $user->getFirstName(), $user->getLastName(),
                $user->getUsername());
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
     * Retrieve Channel, Group, or DM channel information.
     * @param Message $matchingMessage
     * @return \Slack\Channel|\Slack\Group|\Slack\DirectMessageChannel
     */
    public function getChannelGroupOrDM(Message $matchingMessage)
    {
        return $this->client->getChannelGroupOrDMByID($matchingMessage->getChannel());
    }

    /**
     * @return RealTimeClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param $endpoint
     * @param array $parameters
     * @param Message $matchingMessage
     * @return \React\Promise\PromiseInterface
     */
    public function sendRequest($endpoint, array $parameters, Message $matchingMessage)
    {
        return $this->client->apiCall($endpoint, $parameters, false, false);
    }
}

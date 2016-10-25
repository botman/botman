<?php

namespace Mpociot\SlackBot;

use Closure;
use Frlnc\Slack\Core\Commander;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Collection;
use Mpociot\SlackBot\Interfaces\CacheInterface;
use SuperClosure\Serializer;

/**
 * Class SlackBot.
 */
class SlackBot
{
    /**
     * @var \Symfony\Component\HttpFoundation\ParameterBag
     */
    public $payload;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $event;

    /**
     * @var Commander
     */
    protected $commander;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var string
     */
    protected $token;

    /**
     * Messages to listen to.
     * @var array
     */
    protected $listenTo = [];

    /**
     * The fallback message to use, if no match
     * could be heard.
     * @var callable|null
     */
    protected $fallbackMessage;

    /**
     * @var array
     */
    protected $matches = [];
    /**
     * @var CacheInterface
     */
    private $cache;

    const DIRECT_MESSAGE = 'direct_message';

    const PUBLIC_CHANNEL = 'public_channel';

    /**
     * Slack constructor.
     * @param Serializer $serializer
     * @param Commander $commander
     * @param Request $request
     * @param CacheInterface $cache
     */
    public function __construct(Serializer $serializer, Commander $commander, Request $request, CacheInterface $cache)
    {
        /*
         * If the request has a POST parameter called 'payload'
         * we're dealing with an interactive button response.
         */
        if (! is_null($request->get('payload'))) {
            $payloadData = json_decode($request->get('payload'), true);
            $this->payload = collect($payloadData);
            $this->event = collect([
                'channel' => $payloadData['channel']['id'],
                'user' => $payloadData['user']['id'],
            ]);
        } else {
            $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
            $this->event = collect($this->payload->get('event'));
        }

        $this->serializer = $serializer;
        $this->commander = $commander;
        $this->cache = $cache;
    }

    /**
     * @param string $token
     */
    public function initialize($token)
    {
        $this->token = $token;
        $this->commander->setToken($token);
        $this->loadActiveConversation();
    }

    /**
     * Set a fallback message to use if no listener matches.
     *
     * @param callable $callback
     */
    public function fallback($callback)
    {
        $this->fallbackMessage = $callback;
    }

    /**
     * Retrieve the chat message.
     *
     * @return string
     */
    public function getMessage()
    {
        if ($this->payload instanceof Collection || $this->isBot()) {
            return '';
        } else {
            return $this->event->get('text');
        }
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->event->get('user');
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->event->get('channel');
    }

    /**
     * @return $this|static
     */
    public function getConversationAnswer()
    {
        if ($this->payload instanceof Collection) {
            return Answer::create($this->payload['actions'][0]['name'])
                ->setValue($this->payload['actions'][0]['value'])
                ->setCallbackId($this->payload['callback_id']);
        } else {
            return Answer::create($this->event->get('text'));
        }
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return $this->event->has('bot_id');
    }

    /**
     * Get the parameter names for the route.
     *
     * @return array
     */
    protected function compileParameterNames($value)
    {
        preg_match_all('/\{(.*?)\}/', $value, $matches);

        return array_map(function ($m) {
            return trim($m, '?');
        }, $matches[1]);
    }

    /**
     * @param $pattern the $pattern to listen for
     * @param Closure $callback the callback to execute
     * @param string $in the channel type to listen to (either direct message or public channel)
     * @return $this
     */
    public function hears($pattern, Closure $callback, $in = null)
    {
        $this->listenTo[] = [
            'pattern' => $pattern,
            'callback' => $callback,
            'in' => $in,
        ];

        return $this;
    }

    /**
     * Try to match messages with the ones we should
     * listen to.
     */
    public function listen()
    {
        $heardMessage = false;
        foreach ($this->listenTo as $messageData) {
            $pattern = $messageData['pattern'];
            $callback = $messageData['callback'];

            if ($this->isMessageMatching($pattern, $matches) && $this->isChannelValid($this->getChannel(), $messageData['in'])) {
                $heardMessage = true;
                $parameters = array_combine($this->compileParameterNames($pattern), array_slice($matches, 1));
                $this->matches = $parameters;
                array_unshift($parameters, $this);
                call_user_func_array($callback, $parameters);
            }
        }
        if ($heardMessage === false && !$this->isBot() && is_callable($this->fallbackMessage)) {
            call_user_func($this->fallbackMessage, $this);
        }
    }

    /**
     * @param string $pattern
     * @param array $matches
     * @return int
     */
    protected function isMessageMatching($pattern, &$matches)
    {
        $message = preg_replace('/\{(\w+?)\}/', '(.*)', $pattern);

        return preg_match('/'.$message.'/i', $this->getMessage(), $matches);
    }

    /**
     * @param string|Question $message
     * @param array $additionalParameters
     * @return $this
     */
    public function reply($message, $additionalParameters = [])
    {
        $parameters = array_merge([
            'token' => $this->payload->get('token'),
            'channel' => $this->getChannel(),
            'text' => $message,
        ], $additionalParameters);
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['text'] = '';
            $parameters['attachments'] = json_encode([$message->toArray()]);
        }
        $this->commander->execute('chat.postMessage', $parameters);

        return $this;
    }

    /**
     * @param string|Question $message
     * @param array $additionalParameters
     * @return $this
     */
    public function replyPrivate($message, $additionalParameters = [])
    {
        $privateChannel = [
            'channel' => $this->getUser()
        ];
        return $this->reply($message, array_merge($additionalParameters, $privateChannel));
    }

    /**
     * @param Conversation $instance
     */
    public function startConversation(Conversation $instance)
    {
        $instance->setBot($this);
        $instance->run();
    }

    /**
     * @param Conversation $instance
     * @param array|Closure $next
     */
    public function storeConversation(Conversation $instance, $next)
    {
        $this->cache->put($this->getConversationIdentifier(), [
            'conversation' => $instance,
            'next' => is_array($next) ? $this->prepareCallbacks($next) : $this->serializer->serialize($next),
        ], 30);
    }

    /**
     * Prepare an array of pattern / callbacks before
     * caching them.
     *
     * @param array $callbacks
     * @return array
     */
    protected function prepareCallbacks(array $callbacks)
    {
        foreach ($callbacks as &$callback) {
            $callback['callback'] = $this->serializer->serialize($callback['callback']);
        }

        return $callbacks;
    }

    /**
     * Look for active conversations and clear the payload
     * if a conversation is found.
     */
    protected function loadActiveConversation()
    {
        if (! $this->isBot() && $this->cache->has($this->getConversationIdentifier())) {
            $convo = $this->cache->pull($this->getConversationIdentifier());
            $next = false;
            $parameters = [];

            if (is_array($convo['next'])) {
                foreach ($convo['next'] as $callback) {
                    if ($this->isMessageMatching($callback['pattern'], $matches)) {
                        $parameters = array_combine($this->compileParameterNames($callback['pattern']), array_slice($matches, 1));
                        $this->matches = $parameters;
                        $next = $this->serializer->unserialize($callback['callback']);
                        break;
                    }
                }
            } else {
                $next = $this->serializer->unserialize($convo['next']);
            }

            if (is_callable($next)) {
                array_unshift($parameters, $this->getConversationAnswer());
                array_push($parameters, $convo['conversation']);
                call_user_func_array($next, $parameters);
            }

            // Unset payload for possible other listeners
            $this->clearPayload();
        }
    }

    /**
     * @return string
     */
    protected function getConversationIdentifier()
    {
        return 'conversation:'.$this->getUser().'-'.$this->getChannel();
    }

    /**
     * Clear the payload object.
     */
    protected function clearPayload()
    {
        if ($this->payload instanceof Collection) {
        } else {
            $this->payload->replace();
        }
        $this->event = collect();
    }

    /**
     * @param $givenChannel
     * @param $allowedChannel
     * @return bool
     */
    protected function isChannelValid($givenChannel, $allowedChannel)
    {
        /*
         * If the Slack channel starts with a "D" it's a direct message,
         * if it starts with a "C" it is a public channel.
         */
        if ($allowedChannel === self::DIRECT_MESSAGE) {
            return strtolower($givenChannel[0]) === 'd';
        } elseif ($allowedChannel === self::PUBLIC_CHANNEL) {
            return strtolower($givenChannel[0]) === 'c';
        }

        return true;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return array
     */
    public function getMatches()
    {
        return $this->matches;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return [
            'payload',
            'event',
            'commander',
            'serializer',
            'token',
            'cache',
            'matches',
        ];
    }
}

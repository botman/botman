<?php

namespace Mpociot\BotMan;

use Closure;
use SuperClosure\Serializer;
use Mpociot\BotMan\Drivers\Driver;
use Mpociot\BotMan\Interfaces\CacheInterface;
use Mpociot\BotMan\Interfaces\MiddlewareInterface;

/**
 * Class BotMan.
 */
class BotMan
{
    /** @var \Symfony\Component\HttpFoundation\ParameterBag */
    public $payload;

    /** @var \Illuminate\Support\Collection */
    protected $event;

    /** @var Serializer */
    protected $serializer;

    /** @var Message */
    protected $message;

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

    /** @var array */
    protected $matches = [];

    /** @var Driver */
    protected $driver;

    /** @var array */
    protected $config = [];

    /** @var array */
    protected $middleware = [];

    /** @var CacheInterface */
    private $cache;

    /** @var bool */
    protected $loadedConversation = false;

    const DIRECT_MESSAGE = 'direct_message';

    const PUBLIC_CHANNEL = 'public_channel';

    /**
     * BotMan constructor.
     * @param Serializer $serializer
     * @param CacheInterface $cache
     * @param Driver $driver
     */
    public function __construct(Serializer $serializer, CacheInterface $cache, Driver $driver)
    {
        $this->serializer = $serializer;
        $this->cache = $cache;
        $this->message = new Message('', '', '');
        $this->driver = $driver;

        $this->loadActiveConversation();
    }

    /**
     * @param MiddlewareInterface $middleware
     */
    public function middleware(MiddlewareInterface $middleware)
    {
        $this->middleware[] = $middleware;
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
     * @return Driver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $messages = $this->getDriver()->getMessages();

        foreach ($this->middleware as $middleware) {
            foreach ($messages as &$message) {
                $middleware->handle($message);
            }
        }

        return $messages;
    }

    /**
     * @return Answer
     */
    public function getConversationAnswer()
    {
        return $this->getDriver()->getConversationAnswer($this->message);
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return $this->getDriver()->isBot();
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
     * @param string $pattern the pattern to listen for
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

            foreach ($this->getMessages() as $message) {
                if ($this->isMessageMatching($message, $pattern, $matches) && $this->isChannelValid($message->getChannel(), $messageData['in'])) {
                    $this->message = $message;
                    $heardMessage = true;
                    $parameters = array_combine($this->compileParameterNames($pattern), array_slice($matches, 1));
                    $this->matches = $parameters;
                    array_unshift($parameters, $this);
                    call_user_func_array($callback, $parameters);
                }
            }
        }
        if ($heardMessage === false && ! $this->isBot() && is_callable($this->fallbackMessage) && $this->loadedConversation === false) {
            $this->message = $this->getMessages()[0];
            call_user_func($this->fallbackMessage, $this);
        }
    }

    /**
     * @param Message $message
     * @param string $pattern
     * @param array $matches
     * @return int
     */
    protected function isMessageMatching(Message $message, $pattern, &$matches)
    {
        $matches = [];
        // Try middleware first
        foreach ($this->middleware as $middleware) {
            if ($middleware->isMessageMatching($message, $pattern)) {
                return true;
            }
        }

        $text = preg_replace('/\{(\w+?)\}/', '(.*)', $pattern);

        return preg_match('/'.$text.'/i', $message->getMessage(), $matches);
    }

    /**
     * @param string|Question $message
     * @param array $additionalParameters
     * @return $this
     */
    public function reply($message, $additionalParameters = [])
    {
        $this->getDriver()->reply($message, $this->message, $additionalParameters);

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
            'channel' => $this->message->getUser(),
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
        $this->cache->put($this->message->getConversationIdentifier(), [
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
        if ($this->isBot() === false) {
            foreach ($this->getMessages() as $message) {
                if ($this->cache->has($message->getConversationIdentifier())) {
                    $convo = $this->cache->pull($message->getConversationIdentifier());
                    $next = false;
                    $parameters = [];

                    if (is_array($convo['next'])) {
                        foreach ($convo['next'] as $callback) {
                            if ($this->isMessageMatching($message, $callback['pattern'], $matches)) {
                                $this->message = $message;
                                $parameters = array_combine($this->compileParameterNames($callback['pattern']), array_slice($matches, 1));
                                $this->matches = $parameters;
                                $next = $this->serializer->unserialize($callback['callback']);
                                break;
                            }
                        }
                    } else {
                        $this->message = $message;
                        $next = $this->serializer->unserialize($convo['next']);
                    }

                    if (is_callable($next)) {
                        array_unshift($parameters, $this->getConversationAnswer());
                        array_push($parameters, $convo['conversation']);
                        call_user_func_array($next, $parameters);
                        // Mark conversation as loaded to avoid triggering the fallback method
                        $this->loadedConversation = true;
                    }
                }
            }
        }
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
     * @return array
     */
    public function getMatches()
    {
        return $this->matches;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return [
            'payload',
            'event',
            'driver',
            'message',
            'serializer',
            'cache',
            'matches',
        ];
    }
}

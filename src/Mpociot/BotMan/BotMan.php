<?php

namespace Mpociot\BotMan;

use Closure;
use Opis\Closure\SerializableClosure;
use Mpociot\BotMan\Traits\VerifiesServices;
use Mpociot\BotMan\Interfaces\CacheInterface;
use Mpociot\BotMan\Interfaces\DriverInterface;
use Mpociot\BotMan\Interfaces\MiddlewareInterface;

/**
 * Class BotMan.
 */
class BotMan
{
    use VerifiesServices;

    /** @var \Symfony\Component\HttpFoundation\ParameterBag */
    public $payload;

    /** @var \Illuminate\Support\Collection */
    protected $event;

    /** @var Message */
    protected $message;

    /** @var string */
    protected $driverName;

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

    /** @var DriverInterface */
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
     * @param CacheInterface $cache
     * @param DriverInterface $driver
     * @param array $config
     */
    public function __construct(CacheInterface $cache, DriverInterface $driver, $config = [])
    {
        $this->cache = $cache;
        $this->message = new Message('', '', '');
        $this->driver = $driver;
        $this->config = $config;

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
     * @return DriverInterface
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
                $middleware->handle($message, $this->getDriver());
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
     * @param Closure|string $callback the callback to execute. Either a closuer or a Class@method notation
     * @param string $in the channel type to listen to (either direct message or public channel)
     * @return $this
     */
    public function hears($pattern, $callback, $in = null)
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

            if (! $callback instanceof Closure) {
                list($class, $method) = explode('@', $callback);
                $callback = [new $class, $method];
            }

            foreach ($this->getMessages() as $message) {
                if ($this->isMessageMatching($message, $pattern, $matches) && $this->isChannelValid($message->getChannel(), $messageData['in']) && $this->loadedConversation === false) {
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

        $messageText = $message->getMessage();
        $answerText = $this->getConversationAnswer()->getValue();

        $pattern = str_replace('/', '\/', $pattern);
        $text = '/^'.preg_replace('/\{(\w+?)\}/', '(.*)', $pattern).'$/i';
        $regexMatched = (bool) preg_match($text, $messageText, $matches) || (bool) preg_match($text, $answerText, $matches);

        // Try middleware first
        foreach ($this->middleware as $middleware) {
            return $middleware->isMessageMatching($message, $pattern, $regexMatched);
        }

        return $regexMatched;
    }

    /**
     * @param string|Question $message
     * @param string|array $channel
     * @param DriverInterface|null $driver
     * @return $this
     */
    public function say($message, $channel, $driver = null)
    {
        if (is_null($driver)) {
            $drivers = DriverManager::getConfiguredDrivers($this->config);
        } else {
            $drivers = [DriverManager::loadFromName($driver, $this->config)];
        }

        foreach ($drivers as $driver) {
            $matchMessage = new Message('', '', $channel);
            /* @var $driver DriverInterface */
            $driver->reply($message, $matchMessage, []);
        }

        return $this;
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
            'next' => is_array($next) ? $this->prepareCallbacks($next) : serialize(new SerializableClosure($next)),
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
            $callback['callback'] = serialize(new SerializableClosure($callback['callback']));
        }

        return $callbacks;
    }

    /**
     * Look for active conversations and clear the payload
     * if a conversation is found.
     */
    public function loadActiveConversation()
    {
        $this->loadedConversation = false;
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
                                $next = unserialize($callback['callback']);
                                break;
                            }
                        }
                    } else {
                        $this->message = $message;
                        $next = unserialize($convo['next']);
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
     * Load driver on wakeup.
     */
    public function __wakeup()
    {
        $this->driver = DriverManager::loadFromName($this->driverName, $this->config);
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $this->driverName = $this->driver->getName();

        return [
            'payload',
            'event',
            'driverName',
            'message',
            'cache',
            'matches',
            'config',
        ];
    }
}

<?php

namespace Mpociot\BotMan;

use Closure;
use UnexpectedValueException;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Traits\ProvidesStorage;
use Mpociot\BotMan\Traits\VerifiesServices;
use Mpociot\BotMan\Interfaces\UserInterface;
use Mpociot\BotMan\Interfaces\CacheInterface;
use Mpociot\BotMan\Interfaces\DriverInterface;
use Mpociot\BotMan\Interfaces\StorageInterface;
use Mpociot\BotMan\Traits\HandlesConversations;
use Mpociot\BotMan\Interfaces\MiddlewareInterface;
use Mpociot\BotMan\Conversations\InlineConversation;

/**
 * Class BotMan.
 */
class BotMan
{
    use VerifiesServices,
        ProvidesStorage,
        HandlesConversations;

    /**
     * regular expression to capture named parameters but not quantifiers
     * captures {name}, but not {1}, {1,}, or {1,2}.
     */
    const PARAM_NAME_REGEX = '/\{((?:(?!\d+,?\d+?)\w)+?)\}/';

    /** @var \Symfony\Component\HttpFoundation\ParameterBag */
    public $payload;

    /** @var \Illuminate\Support\Collection */
    protected $event;

    /** @var Message */
    protected $message;

    /** @var string */
    protected $driverName;

    /** @var array|null */
    protected $currentConversationData;

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
    protected $groupAttributes = [];

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

    /** @var StorageInterface */
    protected $storage;

    /** @var bool */
    protected $loadedConversation = false;

    /**
     * BotMan constructor.
     * @param CacheInterface $cache
     * @param DriverInterface $driver
     * @param array $config
     * @param StorageInterface $storage
     */
    public function __construct(CacheInterface $cache, DriverInterface $driver, $config, StorageInterface $storage)
    {
        $this->cache = $cache;
        $this->message = new Message('', '', '');
        $this->driver = $driver;
        $this->config = $config;
        $this->storage = $storage;
    }

    /**
     * @param MiddlewareInterface|array $middleware
     */
    public function middleware($middleware)
    {
        if (! is_array($middleware)) {
            $middleware = [$middleware];
        }

        $this->middleware = Collection::make($middleware)->filter(function ($item) {
            return $item instanceof MiddlewareInterface;
        })->merge($this->middleware)->toArray();
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
     * @param DriverInterface $driver
     */
    public function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;
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
        return $this->getDriver()->getMessages();
    }

    /**
     * @param Message $message
     * @param array $middleware
     * @return Message
     */
    protected function applyMiddleware(Message &$message, array $middleware)
    {
        foreach ($middleware as $middle) {
            $middle->handle($message, $this->getDriver());
        }

        return $message;
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
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->getDriver()->getUser($this->getMessage());
    }

    /**
     * Get the parameter names for the route.
     *
     * @param $value
     * @return array
     */
    protected function compileParameterNames($value)
    {
        preg_match_all(self::PARAM_NAME_REGEX, $value, $matches);

        return array_map(function ($m) {
            return trim($m, '?');
        }, $matches[1]);
    }

    /**
     * @param string $pattern the pattern to listen for
     * @param Closure|string $callback the callback to execute. Either a closure or a Class@method notation
     * @param string $in the channel type to listen to (either direct message or public channel)
     * @return Command
     */
    public function hears($pattern, $callback, $in = null)
    {
        $command = new Command($pattern, $callback, $in);
        $command->applyGroupAttributes($this->groupAttributes);

        $this->listenTo[] = $command;

        return $command;
    }

    /**
     * Create a command group with shared attributes.
     *
     * @param  array  $attributes
     * @param  \Closure  $callback
     */
    public function group(array $attributes, Closure $callback)
    {
        $this->groupAttributes = $attributes;

        call_user_func($callback, $this);

        $this->groupAttributes = [];
    }

    /**
     * Try to match messages with the ones we should
     * listen to.
     */
    public function listen()
    {
        $this->loadActiveConversation();

        $heardMessage = false;
        foreach ($this->listenTo as $command) {
            $messageData = $command->toArray();
            $pattern = $messageData['pattern'];
            $callback = $messageData['callback'];

            if (! $callback instanceof Closure) {
                if (strpos($callback, '@') === false) {
                    $callback = $this->makeInvokableAction($callback);
                }

                list($class, $method) = explode('@', $callback);
                $callback = [new $class($this), $method];
            }

            foreach ($this->getMessages() as $message) {
                $message = $this->applyMiddleware($message, $this->middleware);
                $message = $this->applyMiddleware($message, $messageData['middleware']);

                if (! $this->isBot() &&
                    $this->isMessageMatching($message, $pattern, $matches, $messageData['middleware']) &&
                    $this->isDriverValid($this->driver->getName(), $messageData['driver']) &&
                    $this->isChannelValid($message->getChannel(), $messageData['channel']) &&
                    $this->loadedConversation === false
                ) {
                    $this->message = $message;
                    $heardMessage = true;
                    $parameterNames = $this->compileParameterNames($pattern);
                    $matches = array_slice($matches, 1);
                    if (count($parameterNames) === count($matches)) {
                        $parameters = array_combine($parameterNames, $matches);
                    } else {
                        $parameters = $matches;
                    }
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
     * @param array $messageMiddleware
     * @return int
     */
    protected function isMessageMatching(Message $message, $pattern, &$matches, $messageMiddleware = [])
    {
        $matches = [];

        $messageText = $message->getMessage();
        $answerText = $this->getConversationAnswer()->getValue();

        $pattern = str_replace('/', '\/', $pattern);
        $text = '/^'.preg_replace(self::PARAM_NAME_REGEX, '(.*)', $pattern).'$/i';
        $regexMatched = (bool) preg_match($text, $messageText, $matches) || (bool) preg_match($text, $answerText, $matches);

        // Try middleware first
        $mergedMiddleware = array_merge($this->middleware, $messageMiddleware);
        if (count($mergedMiddleware)) {
            return Collection::make($mergedMiddleware)->reject(function ($middleware) use ($message, $pattern, $regexMatched) {
                return $middleware->isMessageMatching($message, $pattern, $regexMatched);
            })->isEmpty() === true;
        }

        return $regexMatched;
    }

    /**
     * @param string|Question $message
     * @param string|array $channel
     * @param DriverInterface|null $driver
     * @param array $additionalParameters
     * @return $this
     */
    public function say($message, $channel, $driver = null, $additionalParameters = [])
    {
        if (is_null($driver)) {
            $drivers = DriverManager::getConfiguredDrivers($this->config);
        } else {
            $drivers = [DriverManager::loadFromName($driver, $this->config)];
        }

        foreach ($drivers as $driver) {
            $matchMessage = new Message('', '', $channel);
            /* @var $driver DriverInterface */
            $driver->reply($message, $matchMessage, $additionalParameters);
        }

        return $this;
    }

    /**
     * @param string|Question $question
     * @param array|Closure $next
     * @param array $additionalParameters
     * @return $this
     */
    public function ask($question, $next, $additionalParameters = [])
    {
        $this->reply($question, $additionalParameters);
        $this->storeConversation(new InlineConversation, $next, $question, $additionalParameters);

        return $this;
    }

    /**
     * @return $this
     */
    public function types()
    {
        $this->getDriver()->types($this->message);

        return $this;
    }

    /**
     * @param int $seconds Number of seconds to wait
     * @return $this
     */
    public function typesAndWaits($seconds)
    {
        $this->getDriver()->types($this->message);
        sleep($seconds);

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
     * Return a random message.
     * @param array $messages
     * @return $this
     */
    public function randomReply(array $messages)
    {
        return $this->reply($messages[array_rand($messages)]);
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
     * @param string $driverName
     * @param string|array $allowedDrivers
     * @return bool
     */
    protected function isDriverValid($driverName, $allowedDrivers)
    {
        if (! is_null($allowedDrivers)) {
            return Collection::make($allowedDrivers)->contains($driverName);
        }

        return true;
    }

    /**
     * @param $givenChannel
     * @param $allowedChannel
     * @return bool
     */
    protected function isChannelValid($givenChannel, $allowedChannel)
    {
        return $givenChannel == $allowedChannel || $allowedChannel === null;
    }

    /**
     * Make an action for an invokable controller.
     *
     * @param string $action
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    protected function makeInvokableAction($action)
    {
        if (! method_exists($action, '__invoke')) {
            throw new UnexpectedValueException(sprintf(
                'Invalid hears action: [%s]', $action
            ));
        }

        return $action.'@__invoke';
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
     * @param string $name
     * @param mixed $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->getDriver(), $name)) {
            // Add the current message to the passed arguments
            array_push($arguments, $this->getMessage());

            return call_user_func_array([$this->getDriver(), $name], $arguments);
        }

        throw new \BadMethodCallException('Method ['.$name.'] does not exist.');
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
            'storage',
            'message',
            'cache',
            'matches',
            'config',
        ];
    }
}

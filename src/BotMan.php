<?php

namespace BotMan\BotMan;

use BotMan\BotMan\Commands\Command;
use BotMan\BotMan\Commands\ConversationManager;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Exceptions\Base\BotManException;
use BotMan\BotMan\Exceptions\Core\BadMethodCallException;
use BotMan\BotMan\Exceptions\Core\UnexpectedValueException;
use BotMan\BotMan\Handlers\ExceptionHandler;
use BotMan\BotMan\Interfaces\CacheInterface;
use BotMan\BotMan\Interfaces\DriverEventInterface;
use BotMan\BotMan\Interfaces\DriverInterface;
use BotMan\BotMan\Interfaces\ExceptionHandlerInterface;
use BotMan\BotMan\Interfaces\Middleware\Heard;
use BotMan\BotMan\Interfaces\StorageInterface;
use BotMan\BotMan\Interfaces\UserInterface;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\Contact;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Conversations\InlineConversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Matcher;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Middleware\MiddlewareManager;
use BotMan\BotMan\Traits\HandlesConversations;
use BotMan\BotMan\Traits\HandlesExceptions;
use BotMan\BotMan\Traits\ProvidesStorage;
use Closure;
use Illuminate\Support\Collection;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BotMan.
 */
class BotMan
{
    use ProvidesStorage,
        HandlesConversations,
        HandlesExceptions;

    /** @var \Illuminate\Support\Collection */
    protected $event;

    /** @var Command */
    protected $command;

    /** @var IncomingMessage */
    protected $message;

    /** @var OutgoingMessage|Question */
    protected $outgoingMessage;

    /** @var string */
    protected $driverName;

    /** @var array|null */
    protected $currentConversationData;

    /** @var ExceptionHandlerInterface */
    protected $exceptionHandler;

    /**
     * IncomingMessage service events.
     * @var array
     */
    protected $events = [];

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

    /** @var MiddlewareManager */
    public $middleware;

    /** @var ConversationManager */
    protected $conversationManager;

    /** @var CacheInterface */
    private $cache;

    /** @var ContainerInterface */
    protected $container;

    /** @var StorageInterface */
    protected $storage;

    /** @var Matcher */
    protected $matcher;

    /** @var bool */
    protected $loadedConversation = false;

    /** @var bool */
    protected $firedDriverEvents = false;

    /** @var bool */
    protected $runsOnSocket = false;

    /**
     * BotMan constructor.
     * @param CacheInterface $cache
     * @param DriverInterface $driver
     * @param array $config
     * @param StorageInterface $storage
     */
    public function __construct(CacheInterface $cache, DriverInterface $driver, $config, StorageInterface $storage, ?Matcher $matcher = null)
    {
        $this->config = $config;
        $this->config['bot_id'] = $this->config['bot_id'] ?? '';

        $this->cache = $cache;
        $this->message = new IncomingMessage('', '', '', null, $this->config['bot_id']);
        $this->driver = $driver;
        $this->storage = $storage;
        $this->matcher = new Matcher();
        $this->middleware = new MiddlewareManager($this);
        $this->conversationManager = new ConversationManager($matcher);
        $this->exceptionHandler = new ExceptionHandler();
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
     * @param string $name The Driver name or class
     */
    public function loadDriver($name)
    {
        $this->driver = DriverManager::loadFromName($name, $this->config);
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
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
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
     * Retrieve the chat message that are sent from bots.
     *
     * @return array
     */
    public function getBotMessages()
    {
        return Collection::make($this->getDriver()->getMessages())->filter(function (IncomingMessage $message) {
            return $message->isFromBot();
        })->toArray();
    }

    /**
     * @return Answer
     */
    public function getConversationAnswer()
    {
        return $this->getDriver()->getConversationAnswer($this->message);
    }

    /**
     * @param bool $running
     * @return bool
     */
    public function runsOnSocket($running = null)
    {
        if (\is_bool($running)) {
            $this->runsOnSocket = $running;
        }

        return $this->runsOnSocket;
    }

    /**
     * @return UserInterface
     */
    public function getUser()
    {
        if ($user = $this->cache->get('user_' . $this->driver->getName() . '_' . $this->getMessage()->getSender())) {
            return $user;
        }

        $user = $this->getDriver()->getUser($this->getMessage());
        $this->cache->put(
            'user_' . $this->driver->getName() . '_' . $user->getId(),
            $user,
            $this->config['user_cache_time'] ?? 30
        );

        return $user;
    }

    /**
     * Get the parameter names for the route.
     *
     * @param $value
     * @return array
     */
    protected function compileParameterNames($value)
    {
        preg_match_all(Matcher::PARAM_NAME_REGEX, $value, $matches);

        return array_map(function ($m) {
            return trim($m, '?');
        }, $matches[1]);
    }

    /**
     * @param array|string $pattern the pattern to listen for
     * @param Closure|string $callback the callback to execute. Either a closure or a Class@method notation
     * @param string $in the channel type to listen to (either direct message or public channel)
     * @return Command
     */
    public function hears($pattern, $callback, $in = null)
    {
        if (is_array($pattern)) {
            $pattern = '(?|' . implode('|', $pattern) . ')';
        }

        $command = new Command($pattern, $callback, $in);
        $command->applyGroupAttributes($this->groupAttributes);

        $this->conversationManager->listenTo($command);

        return $command;
    }

    /**
     * Listen for messaging service events.
     *
     * @param array|string $names
     * @param Closure|string $callback
     */
    public function on($names, $callback)
    {
        if (!is_array($names)) {
            $names = [$names];
        }

        $callable = $this->getCallable($callback);

        foreach ($names as $name) {
            $this->events[] = [
                'name' => $name,
                'callback' => $callable,
            ];
        }
    }

    /**
     * Listening for image files.
     *
     * @param $callback
     * @return Command
     */
    public function receivesImages($callback)
    {
        return $this->hears(Image::PATTERN, $callback);
    }

    /**
     * Listening for video files.
     *
     * @param $callback
     * @return Command
     */
    public function receivesVideos($callback)
    {
        return $this->hears(Video::PATTERN, $callback);
    }

    /**
     * Listening for audio files.
     *
     * @param $callback
     * @return Command
     */
    public function receivesAudio($callback)
    {
        return $this->hears(Audio::PATTERN, $callback);
    }

    /**
     * Listening for location attachment.
     *
     * @param $callback
     * @return Command
     */
    public function receivesLocation($callback)
    {
        return $this->hears(Location::PATTERN, $callback);
    }

    /**
     * Listening for contact attachment.
     *
     * @param $callback
     *
     * @return Command
     */
    public function receivesContact($callback)
    {
        return $this->hears(Contact::PATTERN, $callback);
    }

    /**
     * Listening for files attachment.
     *
     * @param $callback
     * @return Command
     */
    public function receivesFiles($callback)
    {
        return $this->hears(File::PATTERN, $callback);
    }

    /**
     * Create a command group with shared attributes.
     *
     * @param  array $attributes
     * @param  \Closure $callback
     */
    public function group(array $attributes, Closure $callback)
    {
        $previousGroupAttributes = $this->groupAttributes;
        $this->groupAttributes = array_merge_recursive($previousGroupAttributes, $attributes);

        \call_user_func($callback, $this);

        $this->groupAttributes = $previousGroupAttributes;
    }

    /**
     * Fire potential driver event callbacks.
     */
    protected function fireDriverEvents()
    {
        $driverEvent = $this->getDriver()->hasMatchingEvent();
        if ($driverEvent instanceof DriverEventInterface) {
            $this->firedDriverEvents = true;

            Collection::make($this->events)->filter(function ($event) use ($driverEvent) {
                return $driverEvent->getName() === $event['name'];
            })->each(function ($event) use ($driverEvent) {
                /**
                 * Load the message, so driver events can reply.
                 */
                $messages = $this->getDriver()->getMessages();
                if (isset($messages[0])) {
                    $this->message = $messages[0];
                }

                \call_user_func_array($event['callback'], [$driverEvent->getPayload(), $this]);
            });
        }
    }

    /**
     * Try to match messages with the ones we should
     * listen to.
     */
    public function listen()
    {
        try {
            $isVerificationRequest = $this->verifyServices();

            if (!$isVerificationRequest) {
                $this->fireDriverEvents();

                if ($this->firedDriverEvents === false) {
                    $this->loadActiveConversation();

                    if ($this->loadedConversation === false) {
                        $this->callMatchingMessages();
                    }
                }

                /*
                * If the driver has a  "messagesHandled" method, call it.
                * This method can be used to trigger driver methods
                * once the messages are handles.
                */
                if (method_exists($this->getDriver(), 'messagesHandled')) {
                    $this->getDriver()->messagesHandled();
                }

                $this->firedDriverEvents = false;
                $this->message = new IncomingMessage('', '', '', null, $this->config['bot_id']);
            }
        } catch (\Throwable $e) {
            $this->exceptionHandler->handleException($e, $this);
        }
    }

    /**
     * Call matching message callbacks.
     */
    protected function callMatchingMessages()
    {
        $matchingMessages = $this->conversationManager->getMatchingMessages(
            $this->getMessages(),
            $this->middleware,
            $this->getConversationAnswer(),
            $this->getDriver()
        );

        foreach ($matchingMessages as $matchingMessage) {
            $this->command = $matchingMessage->getCommand();
            $callback = $this->command->getCallback();

            $callback = $this->getCallable($callback);

            // Set the message first, so it's available for middlewares
            $this->message = $matchingMessage->getMessage();

            $commandMiddleware = Collection::make($this->command->getMiddleware())->filter(function ($middleware) {
                return $middleware instanceof Heard;
            })->toArray();

            $this->message = $this->middleware->applyMiddleware(
                'heard',
                $matchingMessage->getMessage(),
                $commandMiddleware
            );

            $parameterNames = $this->compileParameterNames($this->command->getPattern());

            $parameters = $matchingMessage->getMatches();
            if (\count($parameterNames) !== \count($parameters)) {
                $parameters = array_merge(
                    //First, all named parameters (eg. function ($a, $b, $c))
                    array_filter(
                        $parameters,
                        '\is_string',
                        ARRAY_FILTER_USE_KEY
                    ),
                    //Then, all other unsorted parameters (regex non named results)
                    array_filter(
                        $parameters,
                        '\is_integer',
                        ARRAY_FILTER_USE_KEY
                    )
                );
            }

            $this->matches = $parameters;
            array_unshift($parameters, $this);

            $parameters = $this->conversationManager->addDataParameters($this->message, $parameters);

            if (call_user_func_array($callback, array_values($parameters))) {
                return;
            }
        }

        if (empty($matchingMessages) && empty($this->getBotMessages()) && !\is_null($this->fallbackMessage)) {
            $this->callFallbackMessage();
        }
    }

    /**
     * Call the fallback method.
     */
    protected function callFallbackMessage()
    {
        $messages = $this->getMessages();

        if (!isset($messages[0])) {
            return;
        }

        $this->message = $messages[0];

        $this->fallbackMessage = $this->getCallable($this->fallbackMessage);

        \call_user_func($this->fallbackMessage, $this);
    }

    /**
     * Verify service webhook URLs.
     *
     * @return bool
     */
    protected function verifyServices()
    {
        return DriverManager::verifyServices($this->config);
    }

    /**
     * @param string|Question|OutgoingMessage $message
     * @param string|array $recipients
     * @param string|DriverInterface|null $driver
     * @param array $additionalParameters
     * @return Response
     * @throws BotManException
     */
    public function say($message, $recipients, $driver = null, $additionalParameters = [])
    {
        if ($driver === null && $this->driver === null) {
            throw new BotManException('The current driver can\'t be NULL');
        }

        $previousDriver = $this->driver;
        $previousMessage = $this->message;

        if ($driver instanceof DriverInterface) {
            $this->setDriver($driver);
        } elseif (\is_string($driver)) {
            $this->setDriver(DriverManager::loadFromName($driver, $this->config));
        }

        $recipients = \is_array($recipients) ? $recipients : [$recipients];

        foreach ($recipients as $recipient) {
            $this->message = new IncomingMessage('', $recipient, '', null, $this->config['bot_id'] ?? '');
            $response = $this->reply($message, $additionalParameters);
        }

        $this->message = $previousMessage;
        $this->driver = $previousDriver;

        return $response;
    }

    /**
     * @param string|Question $question
     * @param array|Closure $next
     * @param array $additionalParameters
     * @param null|string $recipient
     * @param null|string $driver
     * @return Response
     */
    public function ask($question, $next, $additionalParameters = [], $recipient = null, $driver = null)
    {
        if (!\is_null($recipient) && !\is_null($driver)) {
            if (\is_string($driver)) {
                $driver = DriverManager::loadFromName($driver, $this->config);
            }
            $this->message = new IncomingMessage('', $recipient, '', null, $this->config['bot_id']);
            $this->setDriver($driver);
        }

        $response = $this->reply($question, $additionalParameters);
        $this->storeConversation(new InlineConversation, $next, $question, $additionalParameters);

        return $response;
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
     * @param float $seconds Number of seconds to wait
     * @return $this
     */
    public function typesAndWaits(float $seconds)
    {
        $this->getDriver()->typesAndWaits($this->message, $seconds);

        return $this;
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $additionalParameters
     * @return $this
     * @throws BadMethodCallException
     */
    public function sendRequest($endpoint, $additionalParameters = [])
    {
        $driver = $this->getDriver();
        if (method_exists($driver, 'sendRequest')) {
            return $driver->sendRequest($endpoint, $additionalParameters, $this->message);
        }

        throw new BadMethodCallException('The driver ' . $this->getDriver()->getName() . ' does not support low level requests.');
    }

    /**
     * @param string|OutgoingMessage|Question $message
     * @param array $additionalParameters
     * @return mixed
     */
    public function reply($message, $additionalParameters = [])
    {
        $this->outgoingMessage = \is_string($message) ? OutgoingMessage::create($message) : $message;

        return $this->sendPayload($this->getDriver()->buildServicePayload(
            $this->outgoingMessage,
            $this->message,
            $additionalParameters
        ));
    }

    /**
     * @param $payload
     * @return mixed
     */
    public function sendPayload($payload)
    {
        return $this->middleware->applyMiddleware('sending', $payload, [], function ($payload) {
            $this->outgoingMessage = null;

            return $this->getDriver()->sendPayload($payload);
        });
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
     * Make an action for an invokable controller.
     *
     * @param string $action
     * @return string
     * @throws UnexpectedValueException
     */
    protected function makeInvokableAction($action)
    {
        if (!method_exists($action, '__invoke')) {
            throw new UnexpectedValueException(sprintf(
                'Invalid hears action: [%s]',
                $action
            ));
        }

        return $action . '@__invoke';
    }

    /**
     * @param mixed $callback
     * @return mixed
     * @throws UnexpectedValueException
     * @throws NotFoundExceptionInterface
     */
    protected function getCallable($callback)
    {
        if (is_callable($callback)) {
            return $callback;
        }

        if (strpos($callback, '@') === false) {
            $callback = $this->makeInvokableAction($callback);
        }

        [$class, $method] = explode('@', $callback);

        $command = $this->container ? $this->container->get($class) : new $class($this);

        return [$command, $method];
    }

    /**
     * @return array
     */
    public function getMatches()
    {
        return $this->matches;
    }

    /**
     * @return IncomingMessage
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return OutgoingMessage|Question
     */
    public function getOutgoingMessage()
    {
        return $this->outgoingMessage;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws BadMethodCallException
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->getDriver(), $name)) {
            // Add the current message to the passed arguments
            $arguments[] = $this->getMessage();
            $arguments[] = $this;

            return \call_user_func_array([$this->getDriver(), $name], array_values($arguments));
        }

        throw new BadMethodCallException('Method [' . $name . '] does not exist.');
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
            'event',
            'exceptionHandler',
            'driverName',
            'storage',
            'message',
            'cache',
            'matches',
            'matcher',
            'config',
            'middleware',
        ];
    }
}

<?php


namespace BotMan\BotMan\Middleware;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\MiddlewareInterface;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Middleware\DialogFlowV2\Client;
use Google\ApiCore\ApiException;

class DialogFlowV2 implements MiddlewareInterface
{
    /**
     * @var bool
     */
    private $listenForAction;
    /**
     * @var Client
     */
    private $client;

    /**
     * constructor.
     * @param string $lang language
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Create a new Dialogflow middleware instance.
     * @return DialogflowV2
     */
    public static function create($lang = 'en')
    {
        $client = new Client($lang);
        return new static($client);
    }

    /**
     * Restrict the middleware to only listen for dialogflow actions.
     * @param bool $listen
     * @return $this
     */
    public function listenForAction($listen = true)
    {
        $this->listenForAction = $listen;

        return $this;
    }

    /**
     * Handle a captured message.
     *
     * @param IncomingMessage $message
     * @param BotMan $bot
     * @param $next
     *
     * @return mixed
     */
    public function captured(IncomingMessage $message, $next, BotMan $bot)
    {
        return $next($message);
    }

    /**
     * Handle an incoming message.
     *
     * @param IncomingMessage $message
     * @param BotMan $bot
     * @param $next
     *
     * @return mixed
     * @throws ApiException
     */
    public function received(IncomingMessage $message, $next, BotMan $bot)
    {
        $response = $this->client->getResponse($message);

        $message->addExtras('apiReply', $response->getReply());
        $message->addExtras('apiAction', $response->getAction());
        $message->addExtras('apiActionIncomplete', $response->isComplete());
        $message->addExtras('apiIntent', $response->getIntent());
        $message->addExtras('apiParameters', $response->getParameters());
        $message->addExtras('apiContexts', $response->getContexts());

        return $next($message);
    }

    /**
     * @param IncomingMessage $message
     * @param $pattern
     * @param bool $regexMatched Indicator if the regular expression was matched too
     * @return bool
     */
    public function matching(IncomingMessage $message, $pattern, $regexMatched)
    {
        if ($this->listenForAction) {
            $pattern = '/^' . $pattern . '$/i';

            return (bool)preg_match($pattern, $message->getExtras()['apiAction']);
        }

        return true;
    }

    /**
     * Handle a message that was successfully heard, but not processed yet.
     *
     * @param IncomingMessage $message
     * @param BotMan $bot
     * @param $next
     *
     * @return mixed
     */
    public function heard(IncomingMessage $message, $next, BotMan $bot)
    {
        return $next($message);
    }

    /**
     * Handle an outgoing message payload before/after it
     * hits the message service.
     *
     * @param mixed $payload
     * @param BotMan $bot
     * @param $next
     *
     * @return mixed
     */
    public function sending($payload, $next, BotMan $bot)
    {
        return $next($payload);
    }
}
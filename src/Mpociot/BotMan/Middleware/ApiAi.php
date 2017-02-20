<?php

namespace Mpociot\BotMan\Middleware;

use Mpociot\BotMan\Message;
use Mpociot\BotMan\Http\Curl;
use Mpociot\BotMan\Interfaces\HttpInterface;
use Mpociot\BotMan\Interfaces\DriverInterface;
use Mpociot\BotMan\Interfaces\MiddlewareInterface;

class ApiAi implements MiddlewareInterface
{
    /** @var string */
    protected $token;

    /** @var HttpInterface */
    protected $http;

    /** @var string */
    protected $apiUrl = 'https://api.api.ai/v1/query';

    /** @var bool */
    protected $listenForAction = false;

    /**
     * Wit constructor.
     * @param string $token wit.ai access token
     * @param HttpInterface $http
     */
    public function __construct($token, HttpInterface $http)
    {
        $this->token = $token;
        $this->http = $http;
    }

    /**
     * Create a new Wit middleware instance.
     * @param string $token wit.ai access token
     * @return ApiAi
     */
    public static function create($token)
    {
        return new static($token, new Curl());
    }

    /**
     * Restrict the middleware to only listen for API.ai actions.
     * @param  bool $listen
     * @return $this
     */
    public function listenForAction($listen = true)
    {
        $this->listenForAction = $listen;

        return $this;
    }

    /**
     * Handle / modify the message.
     *
     * @param Message $message
     * @param DriverInterface $driver
     */
    public function handle(Message &$message, DriverInterface $driver)
    {
        $response = $this->http->post($this->apiUrl, [], [
            'query' => [$message->getMessage()],
            'sessionId' => md5($message->getChannel()),
            'lang' => 'en',
        ], [
            'Authorization: Bearer '.$this->token,
            'Content-Type: application/json; charset=utf-8',
        ], true);

        $response = json_decode($response->getContent());
        $reply = isset($response->result->speech) ? $response->result->speech : '';
        $action = isset($response->result->action) ? $response->result->action : '';
        $intent = isset($response->result->metadata->intentName) ? $response->result->metadata->intentName : '';
        $parameters = isset($response->result->parameters) ? (array) $response->result->parameters : [];

        $message->addExtras('apiReply', $reply);
        $message->addExtras('apiAction', $action);
        $message->addExtras('apiIntent', $intent);
        $message->addExtras('apiParameters', $parameters);
    }

    /**
     * @param Message $message
     * @param string $test
     * @param bool $regexMatched
     * @return bool
     * @internal param string $test
     */
    public function isMessageMatching(Message $message, $test, $regexMatched)
    {
        if ($this->listenForAction) {
            return $message->getExtras()['apiAction'] === $test;
        }

        return true;
    }
}

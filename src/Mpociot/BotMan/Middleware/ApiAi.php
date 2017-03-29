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

    /** @var stdClass */
    protected $response;

    /** @var string */
    protected $lastResponseHash;

    /** @var string */
    protected $apiUrl = 'https://api.api.ai/v1/query?v=20150910';

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
     * Perform the API.ai API call and cache it for the message.
     * @param  Message $message
     * @return stdClass
     */
    protected function getResponse(Message $message)
    {
        $lastResponseHash = md5($message->getMessage());
        if ($this->lastResponseHash !== $lastResponseHash) {
            $response = $this->http->post($this->apiUrl, [], [
                'query' => [$message->getMessage()],
                'sessionId' => md5($message->getChannel()),
                'lang' => 'en',
            ], [
                'Authorization: Bearer '.$this->token,
                'Content-Type: application/json; charset=utf-8',
            ], true);

            $this->response = json_decode($response->getContent());
            $this->lastResponseHash = $lastResponseHash;
        }

        return $this->response;
    }

    /**
     * Handle / modify the message.
     *
     * @param Message $message
     * @param DriverInterface $driver
     */
    public function handle(Message &$message, DriverInterface $driver)
    {
        $response = $this->getResponse($message);

        $reply = isset($response->result->fulfillment->speech) ? $response->result->fulfillment->speech : '';
        $action = isset($response->result->action) ? $response->result->action : '';
        $actionIncomplete = isset($response->result->actionIncomplete) ? (bool) $response->result->actionIncomplete : false;
        $intent = isset($response->result->metadata->intentName) ? $response->result->metadata->intentName : '';
        $parameters = isset($response->result->parameters) ? (array) $response->result->parameters : [];

        $message->addExtras('apiReply', $reply);
        $message->addExtras('apiAction', $action);
        $message->addExtras('apiActionIncomplete', $actionIncomplete);
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
            $pattern = '/^'.$test.'$/i';

            return (bool) preg_match($pattern, $message->getExtras()['apiAction']);
        }

        return true;
    }
}

<?php

namespace BotMan\BotMan\Middleware;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Http\Curl;
use BotMan\BotMan\Interfaces\HttpInterface;
use BotMan\BotMan\Drivers\WeChat\WeChatDriver;
use BotMan\BotMan\Interfaces\MiddlewareInterface;
use BotMan\BotMan\Drivers\Facebook\FacebookDriver;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

class Botanalytics implements MiddlewareInterface
{
    /** @var array */
    protected $tokens = [];

    /** @var HttpInterface */
    protected $http;

    /** @var array */
    protected $headers = [];

    const API_URL = 'https://botanalytics.co/api/v1/messages/';

    /**
     * @param array $tokens to use
     * @param HttpInterface $http
     */
    public function __construct(array $tokens, HttpInterface $http)
    {
        $this->tokens = $tokens;
        $this->http = $http;
    }

    /**
     * @param string $service
     * @return array
     */
    private function getHeaders(string $service): array
    {
        return [
            'Authorization: Token '.($this->tokens[$service] ?? null),
            'Content-Type: application/json',
        ];
    }

    /**
     * Create a new Botanalytics instance.
     *
     * @param array $tokens
     * @return Botanalytics
     */
    public static function create(array $tokens)
    {
        return new static($tokens, new Curl());
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
     */
    public function received(IncomingMessage $message, $next, BotMan $bot)
    {
        $payload = [];
        $endpoint = null;
        $driver = $bot->getDriver();

        if ($driver instanceof FacebookDriver) {
            $endpoint = 'facebook-messenger';
            $payload = [
                'recipient' => null,
                'timestamp' => $message->getPayload()['timestamp'],
                'message' => json_decode($driver->getContent()),
            ];
        } elseif ($driver instanceof WeChatDriver) {
            $endpoint = 'wechat';
            $payload = [
                'is_sender_bot' => false,
                'message' => json_decode($message->getPayload()),
            ];
        }

        if (! is_null($endpoint)) {
            $this->http->post(self::API_URL.$endpoint.'/', [], $payload, $this->getHeaders($endpoint), true);
        }

        return $next($message);
    }

    /**
     * @param IncomingMessage $message
     * @param string $pattern
     * @param bool $regexMatched Indicator if the regular expression was matched too
     * @return bool
     */
    public function matching(IncomingMessage $message, $pattern, $regexMatched)
    {
        return $regexMatched;
    }

    /**
     * Handle a message that was successfully heard, but not processed yet.
     *
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $message
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
        $driver = $bot->getDriver();
        $endpoint = null;
        $analyticsPayload = [];

        if ($driver instanceof FacebookDriver) {
            $endpoint = 'facebook-messenger';
            $analyticsPayload = [
                'recipient' => $payload['recipient']['id'],
                'timestamp' => round(microtime(true) * 1000),
                'message' => $payload['message'],
            ];
        } elseif ($driver instanceof WeChatDriver) {
            $matchingMessage = $bot->getMessage();
            $endpoint = 'wechat';
            $analyticsPayload = [
                'is_sender_bot' => true,
                'message' => [
                    'ToUserName' => $matchingMessage->getSender(),
                    'FromUserName' => $matchingMessage->getRecipient(),
                    'CreateTime' => time(),
                    'MsgType' => $payload['msgtype'],
                ],
            ];
            if ($payload['msgtype'] === 'text') {
                $analyticsPayload['message']['Content'] = $payload['text']['content'];
            }
        }

        if (! is_null($endpoint)) {
            $this->http->post(self::API_URL.$endpoint.'/', [], $analyticsPayload, $this->getHeaders($endpoint),
                true);
        }

        return $next($payload);
    }
}

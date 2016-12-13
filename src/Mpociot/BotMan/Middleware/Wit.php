<?php

namespace Mpociot\BotMan\Middleware;

use Mpociot\BotMan\Drivers\Driver;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Http\Curl;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Interfaces\HttpInterface;
use Mpociot\BotMan\Interfaces\MiddlewareInterface;

class Wit implements MiddlewareInterface
{
    /** @var string */
    protected $token;

    /** @var float */
    protected $minimumConfidence = 0.5;

    /** @var HttpInterface */
    protected $http;

    /**
     * Wit constructor.
     * @param string $token wit.ai access token
     * @param float $minimumConfidence Minimum confidence value to match against
     * @param HttpInterface $http
     */
    public function __construct($token, $minimumConfidence, HttpInterface $http)
    {
        $this->token = $token;
        $this->http = $http;
    }

    /**
     * Create a new Wit middleware instance.
     * @param string $token wit.ai access token
     * @param float $minimumConfidence
     * @return Wit
     */
    public static function create($token, $minimumConfidence = 0.5)
    {
        return new static($token, $minimumConfidence, new Curl());
    }

    /**
     * Handle / modify the message.
     *
     * @param Message $message
     * @param Driver $driver
     */
    public function handle(Message &$message, Driver $driver)
    {
        $response = $this->http->post('https://api.wit.ai/message?q='.urlencode($message->getMessage()), [], [], [
            'Authorization: Bearer '.$this->token,
        ]);
        $responseData = Collection::make(json_decode($response->getContent(), true));
        $message->addExtras('entities', $responseData->get('entities'));
    }

    /**
     * @param Message $message
     * @param string $test
     * @return bool
     */
    public function isMessageMatching(Message $message, $test)
    {
        $entities = Collection::make($message->getExtras())->get('entities', []);

        foreach ($entities as $name => $entity) {
            if ($name === 'intent') {
                foreach ($entity as $item) {
                    if ($item['value'] === $test && $item['confidence'] >= $this->minimumConfidence) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}

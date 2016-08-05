<?php

namespace Mpociot\SlackBot;

use Cache;
use Closure;
use Frlnc\Slack\Core\Commander;
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
     * Slack constructor.
     * @param Serializer $serializer
     * @param Commander $commander
     */
    public function __construct(Serializer $serializer, Commander $commander)
    {
        $this->payload = request()->json();
        $this->event = collect($this->payload->get('event'));

        $this->serializer = $serializer;
        $this->commander = $commander;
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
     * Retrieve the chat message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->event->get('text', '');
    }

    /**
     * @return bool
     */
    protected function isBot()
    {
        return $this->event->has('bot_id');
    }

    /**
     * @param $message
     * @param Closure $callback
     * @return $this
     */
    public function hears($message, Closure $callback)
    {
        if (collect($message)->map(function ($words) {
            return strtolower($words);
        })->contains(strtolower($this->getMessage()))
        ) {
            $callback($this);
        }

        return $this;
    }

    /**
     * @param $message
     * @param null $channel
     * @return $this
     */
    public function respond($message, $channel = null)
    {
        $this->commander->execute('chat.postMessage', [
            'token' => $this->payload->get('token'),
            'channel' => $channel ? $channel : $this->event->get('channel'),
            'text' => $message,
        ]);

        return $this;
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
     * @param Closure $next
     */
    public function storeConversation(Conversation $instance, Closure $next)
    {
        Cache::put('conversation', [
            'conversation' => $instance,
            'next' => $this->serializer->serialize($next),
        ], 30);
    }

    /**
     * Look for active conversations and clear the payload
     * if a conversation is found.
     */
    protected function loadActiveConversation()
    {
        if (! $this->isBot() && Cache::has($this->getConversationIdentifier())) {
            $convo = Cache::pull($this->getConversationIdentifier());
            $next = $this->serializer->unserialize($convo['next']);

            if (is_callable($next)) {
                $next($this->getMessage(), $convo['conversation']);
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
        return 'conversation:'.$this->event->get('user').'-'.$this->event->get('channel');
    }

    /**
     * Clear the payload object.
     */
    protected function clearPayload()
    {
        $this->payload->replace();
        $this->event = collect();
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
}

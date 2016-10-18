<?php
namespace Mpociot\SlackBot;

use Cache;
use Closure;
use Frlnc\Slack\Core\Commander;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use SuperClosure\Serializer;

/**
 * Class SlackBot
 * @package Mpociot\SlackBot
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
     * @var array
     */
    protected $matches = [];

    /**
     * Slack constructor.
     * @param Serializer $serializer
     * @param Commander $commander
     * @param Request $request
     */
    public function __construct(Serializer $serializer, Commander $commander, Request $request)
    {
        if ($request->has('payload')) {
            $this->payload = collect(json_decode($request->get('payload'), true));
            $this->event   = collect();
        } else {
            $this->payload = $request->json();
            $this->event   = collect($this->payload->get('event'));
        }

        $this->serializer = $serializer;
        $this->commander = $commander;
        $this->request = $request;
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
     * Retrieve the chat message
     *
     * @return string
     */
    public function getMessage()
    {
        if ($this->payload instanceof Collection || $this->isBot()) {
            return '';
        } else {
            return $this->event->get('text');
        }
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->event->get('user');
    }

    public function getConversationReply()
    {
        if ($this->payload instanceof Collection) {
            return $this->payload->toArray();
        } else {
            return $this->event->get('text');
        }
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return $this->event->has('bot_id');
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
     * @param $message
     * @param Closure $callback
     * @return $this
     */
    public function hears($message, Closure $callback)
    {
        $parameterNames = $this->compileParameterNames($message);
        $message = preg_replace('/\{(\w+?)\}/', '(.*)', $message);

        if ( preg_match('/'.$message.'/i', $this->getMessage(), $matches) ) {
            $parameters = array_combine($parameterNames, array_slice($matches, 1));
            $this->matches = $parameters;
            array_unshift($parameters, $this);
            call_user_func_array($callback, $parameters);
        }
        return $this;
    }

    /**
     * @param $message
     * @param array $attachments
     * @param null $channel
     * @return $this
     */
    public function respond($message, $attachments = [], $channel = null)
    {
        $this->commander->execute('chat.postMessage', [
            'token' => $this->payload->get('token'),
            'channel' => $channel ? $channel : $this->event->get('channel'),
            'text' => $message,
            'attachments' => json_encode($attachments)
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
        Cache::put($this->getConversationIdentifier(), [
            'conversation' => $instance,
            'next' => $this->serializer->serialize($next)
        ], 30);
    }

    /**
     * Look for active conversations and clear the payload
     * if a conversation is found
     */
    protected function loadActiveConversation()
    {
        if (!$this->isBot() && Cache::has($this->getConversationIdentifier())) {
            $convo = Cache::pull($this->getConversationIdentifier());
            $next = $this->serializer->unserialize($convo['next']);

            if (is_callable($next)) {
                $next($this->getConversationReply(), $convo['conversation']);
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
        if ($this->payload instanceof Collection) {
            return 'conversation:'.Arr::get($this->payload->toArray(), 'user.id').'-'.Arr::get($this->payload->toArray(), 'channel.id');
        } else {
            return 'conversation:'.$this->event->get('user').'-'.$this->event->get('channel');
        }
    }

    /**
     * Clear the payload object.
     */
    protected function clearPayload()
    {
        if ($this->payload instanceof Collection) {

        } else {
            $this->payload->replace();
        }
        $this->event = collect();
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return array
     */
    public function getMatches()
    {
        return $this->matches;
    }
}
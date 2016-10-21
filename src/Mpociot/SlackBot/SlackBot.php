<?php
namespace Mpociot\SlackBot;

use Cache;
use Closure;
use Frlnc\Slack\Core\Commander;
use Illuminate\Http\Request;
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
        /**
         * If the request has a POST parameter called 'payload'
         * we're dealing with an interactive button response.
         */
        if ($request->has('payload')) {
            $payloadData = json_decode($request->get('payload'), true);
            $this->payload = collect($payloadData);
            $this->event   = collect([
                'channel' => array_get($payloadData, 'channel.id'),
                'user' => array_get($payloadData, 'user.id')
            ]);
        } else {
            $this->payload = $request->json();
            $this->event   = collect($this->payload->get('event'));
        }

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
     * Set a fallback message to use if no listener matches.
     *
     * @param callable $callback
     */
    public function fallback($callback)
    {
        $this->fallbackMessage = $callback;
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
     * @return string
     */
    public function getUser()
    {
        return $this->event->get('user');
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->event->get('channel');
    }

    /**
     * @return $this|static
     */
    public function getConversationAnswer()
    {
        if ($this->payload instanceof Collection) {
            return Answer::create(array_get($this->payload, 'actions.0.name'))
                ->setValue(array_get($this->payload, 'actions.0.value'))
                ->setCallbackId(array_get($this->payload, 'callback_id'));
        } else {
            return Answer::create($this->event->get('text'));
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
        $this->listenTo[] = [
            'message' => $message,
            'callback' => $callback
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
            $message = $messageData['message'];
            $callback = $messageData['callback'];

            $parameterNames = $this->compileParameterNames($message);
            $message = preg_replace('/\{(\w+?)\}/', '(.*)', $message);

            if ( preg_match('/'.$message.'/i', $this->getMessage(), $matches) ) {
                $heardMessage = true;
                $parameters = array_combine($parameterNames, array_slice($matches, 1));
                $this->matches = $parameters;
                array_unshift($parameters, $this);
                call_user_func_array($callback, $parameters);
            }
        }
        if ($heardMessage === false && is_callable($this->fallbackMessage)) {
            call_user_func($this->fallbackMessage, $this);
        }
    }

    /**
     * @param string|Question $message
     * @param null $channel
     * @return $this
     */
    public function respond($message, $channel = null)
    {
        $parameters = [
            'token' => $this->payload->get('token'),
            'channel' => $channel ? $channel : $this->getChannel(),
            'text' => $message,
        ];
        /**
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['text'] = '';
            $parameters['attachments'] = json_encode([$message->toArray()]);
        }
        $this->commander->execute('chat.postMessage', $parameters);
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
                $next($this->getConversationAnswer(), $convo['conversation']);
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
        return 'conversation:'.$this->getUser().'-'.$this->getChannel();
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
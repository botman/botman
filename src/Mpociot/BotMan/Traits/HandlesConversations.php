<?php

namespace Mpociot\BotMan\Traits;

use Closure;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Mpociot\BotMan\Conversation;
use Mpociot\BotMan\DriverManager;
use Opis\Closure\SerializableClosure;
use Mpociot\BotMan\Drivers\SlackRTMDriver;
use Mpociot\BotMan\Interfaces\ShouldQueue;

trait HandlesConversations
{
    /**
     * @param Conversation $instance
     * @param null|string $channel
     * @param null|string $driver
     */
    public function startConversation(Conversation $instance, $channel = null, $driver = null)
    {
        if (! is_null($channel) && ! is_null($driver)) {
            $this->message = new Message('', '', $channel);
            $this->driver = DriverManager::loadFromName($driver, $this->config);
        }
        $instance->setBot($this);
        $instance->run();
    }

    /**
     * @param Conversation $instance
     * @param array|Closure $next
     * @param string|Question $question
     * @param array $additionalParameters
     */
    public function storeConversation(Conversation $instance, $next, $question = null, $additionalParameters = [])
    {
        $this->cache->put($this->message->getConversationIdentifier(), [
            'conversation' => $instance,
            'question' => serialize($question),
            'additionalParameters' => serialize($additionalParameters),
            'next' => $this->prepareCallbacks($next),
            'time' => microtime(),
        ], 30);
    }

    /**
     * Get a stored conversation array from the cache for a given message.
     * @param null|Message $message
     * @return array
     */
    public function getStoredConversation($message = null)
    {
        if (is_null($message)) {
            $message = $this->getMessage();
        }

        $conversation = $this->cache->get($message->getConversationIdentifier());
        if (is_null($conversation)) {
            $conversation = $this->cache->get($message->getOriginatedConversationIdentifier());
        }

        return $conversation;
    }

    /**
     * Remove a stored conversation array from the cache for a given message.
     * @param null|Message $message
     */
    public function removeStoredConversation($message = null)
    {
        /*
         * Only remove it from the cache if it was not modified
         * after we loaded the data from the cache.
         */
        if ($this->getStoredConversation($message)['time'] == $this->currentConversationData['time']) {
            $this->cache->pull($this->message->getConversationIdentifier());
        }
    }

    /**
     * @param Closure $closure
     * @return string
     */
    protected function serializeClosure(Closure $closure)
    {
        if ($this->getDriver()->getName() !== SlackRTMDriver::DRIVER_NAME) {
            return serialize(new SerializableClosure($closure, true));
        }

        return $closure;
    }

    /**
     * @param mixed $closure
     * @return string
     */
    protected function unserializeClosure($closure)
    {
        if ($this->getDriver()->getName() !== SlackRTMDriver::DRIVER_NAME) {
            return unserialize($closure);
        }

        return $closure;
    }

    /**
     * Prepare an array of pattern / callbacks before
     * caching them.
     *
     * @param array|Closure $callbacks
     * @return array
     */
    protected function prepareCallbacks($callbacks)
    {
        if (is_array($callbacks)) {
            foreach ($callbacks as &$callback) {
                $callback['callback'] = $this->serializeClosure($callback['callback']);
            }
        } else {
            $callbacks = $this->serializeClosure($callbacks);
        }

        return $callbacks;
    }

    /**
     * Look for active conversations and clear the payload
     * if a conversation is found.
     */
    public function loadActiveConversation()
    {
        $this->loadedConversation = false;
        if ($this->isBot() === false) {
            foreach ($this->getMessages() as $message) {
                if ($this->cache->has($message->getConversationIdentifier()) || $this->cache->has($message->getOriginatedConversationIdentifier())) {
                    $convo = $this->getStoredConversation($message);

                    if ($convo['conversation']->stopConversation($message) === true) {
                        $this->message = $message;
                        $this->currentConversationData = $convo;
                        $this->removeStoredConversation();
                        break;
                    }
                    if ($convo['conversation']->skipConversation($message) === true) {
                        break;
                    }

                    $next = false;
                    $parameters = [];
                    if (is_array($convo['next'])) {
                        foreach ($convo['next'] as $callback) {
                            if ($this->isMessageMatching($message, $callback['pattern'], $matches)) {
                                $this->message = $message;
                                $this->currentConversationData = $convo;
                                $parameters = array_combine($this->compileParameterNames($callback['pattern']), array_slice($matches, 1));
                                $this->matches = $parameters;
                                $next = $this->unserializeClosure($callback['callback']);
                                break;
                            }
                        }
                    } else {
                        $this->message = $message;
                        $this->currentConversationData = $convo;
                        $next = $this->unserializeClosure($convo['next']);
                    }

                    if (is_callable($next)) {
                        if ($next instanceof SerializableClosure) {
                            $conversation = $convo['conversation'];
                            if (! $conversation instanceof ShouldQueue) {
                                $conversation->setBot($this);
                            }
                            $next = $next->getClosure()->bindTo($conversation, $conversation);
                        }
                        array_unshift($parameters, $this->getConversationAnswer());
                        array_push($parameters, $convo['conversation']);
                        call_user_func_array($next, $parameters);
                        // Mark conversation as loaded to avoid triggering the fallback method
                        $this->loadedConversation = true;
                        $this->removeStoredConversation();
                    }
                }
            }
        }
    }
}

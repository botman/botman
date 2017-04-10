<?php

namespace Mpociot\BotMan\Traits;

use Closure;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Mpociot\BotMan\Conversation;
use Mpociot\BotMan\DriverManager;
use Illuminate\Support\Collection;
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
        ], isset($this->config['conversation_cache_time']) ? $this->config['conversation_cache_time'] : 30);
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
            $this->cache->pull($this->message->getOriginatedConversationIdentifier());
        }
    }

    /**
     * @param Closure $closure
     * @return string
     */
    public function serializeClosure(Closure $closure)
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

        $conversationMessages = Collection::make($this->getMessages())->filter(function ($message) {
            return $this->cache->has($message->getConversationIdentifier()) || $this->cache->has($message->getOriginatedConversationIdentifier());
        })->each(function ($message) {
            $convo = $this->getStoredConversation($message);

            // Should we skip the conversation?
            if ($convo['conversation']->skipConversation($message) === true) {
                return;
            }

            // Or stop it entirely?
            if ($convo['conversation']->stopConversation($message) === true) {
                $this->cache->pull($message->getConversationIdentifier());
                $this->cache->pull($message->getOriginatedConversationIdentifier());

                return;
            }

            // Ongoing conversation - let's find the callback.
            $next = false;
            $parameters = [];
            if (is_array($convo['next'])) {
                foreach ($convo['next'] as $callback) {
                    if ($this->isMessageMatching($message, $callback['pattern'], $matches)) {
                        $parameters = array_combine($this->compileParameterNames($callback['pattern']), array_slice($matches, 1));
                        $this->matches = $parameters;
                        $next = $this->unserializeClosure($callback['callback']);
                        break;
                    }
                }
            } else {
                $next = $this->unserializeClosure($convo['next']);
            }

            $this->message = $message;
            $this->currentConversationData = $convo;

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
        });
    }
}

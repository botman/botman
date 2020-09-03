<?php

namespace BotMan\BotMan\Traits;

use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Interfaces\ShouldQueue;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use Closure;
use Illuminate\Support\Collection;
use Opis\Closure\SerializableClosure;

trait HandlesConversations
{
    /**
     * @param \BotMan\BotMan\Messages\Conversations\Conversation $instance
     * @param null|string $recipient
     * @param null|string $driver
     */
    public function startConversation(Conversation $instance, $recipient = null, $driver = null)
    {
        if (! is_null($recipient) && ! is_null($driver)) {
            $this->message = new IncomingMessage('', $recipient, '');
            $this->driver = DriverManager::loadFromName($driver, $this->config);
        }
        $instance->setBot($this);
        $instance->run();
    }

    /**
     * @param \BotMan\BotMan\Messages\Conversations\Conversation $instance
     * @param array|Closure $next
     * @param string|Question $question
     * @param array $additionalParameters
     */
    public function storeConversation(Conversation $instance, $next, $question = null, $additionalParameters = [])
    {
        $conversation_cache_time = $instance->getConversationCacheTime();

        $this->cache->put($this->message->getConversationIdentifier(), [
            'conversation' => $instance,
            'question' => serialize($question),
            'additionalParameters' => serialize($additionalParameters),
            'next' => $this->prepareCallbacks($next),
            'time' => microtime(),
        ], $conversation_cache_time ?? $this->config['config']['conversation_cache_time'] ?? 30);
    }

    /**
     * Get a stored conversation array from the cache for a given message.
     *
     * @param null|IncomingMessage $message
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
     * Touch and update the current conversation.
     *
     * @return void
     */
    public function touchCurrentConversation()
    {
        if (! is_null($this->currentConversationData)) {
            $touched = $this->currentConversationData;
            $touched['time'] = microtime();

            $this->cache->put($this->message->getConversationIdentifier(), $touched, $this->config['config']['conversation_cache_time'] ?? 30);
        }
    }

    /**
     * Get the question that was asked in the currently stored conversation
     * for a given message.
     *
     * @param null|IncomingMessage $message
     * @return string|Question
     */
    public function getStoredConversationQuestion($message = null)
    {
        $conversation = $this->getStoredConversation($message);

        return unserialize($conversation['question']);
    }

    /**
     * Remove a stored conversation array from the cache for a given message.
     *
     * @param null|IncomingMessage $message
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
        if ($this->getDriver()->serializesCallbacks() && ! $this->runsOnSocket) {
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
        if ($this->getDriver()->serializesCallbacks() && ! $this->runsOnSocket) {
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

        Collection::make($this->getMessages())->reject(function (IncomingMessage $message) {
            return $message->isFromBot();
        })->filter(function (IncomingMessage $message) {
            return $this->cache->has($message->getConversationIdentifier()) || $this->cache->has($message->getOriginatedConversationIdentifier());
        })->each(function ($message) {
            $message = $this->middleware->applyMiddleware('received', $message);
            $message = $this->middleware->applyMiddleware('captured', $message);

            $convo = $this->getStoredConversation($message);

            // Should we skip the conversation?
            if ($convo['conversation']->skipsConversation($message) === true) {
                return;
            }

            // Or stop it entirely?
            if ($convo['conversation']->stopsConversation($message) === true) {
                $this->cache->pull($message->getConversationIdentifier());
                $this->cache->pull($message->getOriginatedConversationIdentifier());

                return;
            }

            $matchingMessages = $this->conversationManager->getMatchingMessages([$message], $this->middleware, $this->getConversationAnswer(), $this->getDriver(), false);
            foreach ($matchingMessages as $matchingMessage) {
                $command = $matchingMessage->getCommand();
                if ($command->shouldStopConversation()) {
                    $this->cache->pull($message->getConversationIdentifier());
                    $this->cache->pull($message->getOriginatedConversationIdentifier());

                    return;
                } elseif ($command->shouldSkipConversation()) {
                    return;
                }
            }

            // Ongoing conversation - let's find the callback.
            $next = false;
            $parameters = [];
            if (is_array($convo['next'])) {
                $toRepeat = false;
                foreach ($convo['next'] as $callback) {
                    if ($this->matcher->isPatternValid($message, $this->getConversationAnswer(), $callback['pattern'])) {
                        $parameterNames = $this->compileParameterNames($callback['pattern']);
                        $matches = $this->matcher->getMatches();

                        if (count($parameterNames) === count($matches)) {
                            $parameters = array_combine($parameterNames, $matches);
                        } else {
                            $parameters = $matches;
                        }
                        $this->matches = $parameters;
                        $next = $this->unserializeClosure($callback['callback']);
                        break;
                    }
                }

                if ($next == false) {
                    //no pattern match
                    //answer probably unexpected (some plain text)
                    //let's repeat question
                    $toRepeat = true;
                }
            } else {
                $next = $this->unserializeClosure($convo['next']);
            }

            $this->message = $message;
            $this->currentConversationData = $convo;

            if (is_callable($next)) {
                $this->callConversation($next, $convo, $message, $parameters);
            } elseif ($toRepeat) {
                $conversation = $convo['conversation'];
                $conversation->setBot($this);
                $conversation->repeat();
                $this->loadedConversation = true;
            }
        });
    }

    /**
     * @param callable $next
     * @param array $convo
     * @param IncomingMessage $message
     * @param array $parameters
     */
    protected function callConversation($next, $convo, IncomingMessage $message, array $parameters)
    {
        /** @var \BotMan\BotMan\Messages\Conversations\Conversation $conversation */
        $conversation = $convo['conversation'];
        if (! $conversation instanceof ShouldQueue) {
            $conversation->setBot($this);
        }
        /*
         * Validate askForImages, askForAudio, etc. calls
         */
        $additionalParameters = Collection::make(unserialize($convo['additionalParameters']));
        if ($additionalParameters->has('__pattern')) {
            if ($this->matcher->isPatternValid($message, $this->getConversationAnswer(), $additionalParameters->get('__pattern'))) {
                $getter = $additionalParameters->get('__getter');
                array_unshift($parameters, $this->getConversationAnswer()->getMessage()->$getter());
                $this->prepareConversationClosure($next, $conversation, $parameters);
            } else {
                if (is_null($additionalParameters->get('__repeat'))) {
                    $conversation->repeat();
                } else {
                    $next = unserialize($additionalParameters->get('__repeat'));
                    array_unshift($parameters, $this->getConversationAnswer());
                    $this->prepareConversationClosure($next, $conversation, $parameters);
                }
            }
        } else {
            array_unshift($parameters, $this->getConversationAnswer());
            $this->prepareConversationClosure($next, $conversation, $parameters);
        }

        // Mark conversation as loaded to avoid triggering the fallback method
        $this->loadedConversation = true;
        $this->removeStoredConversation();
    }

    /**
     * @param Closure $next
     * @param Conversation $conversation
     * @param array $parameters
     */
    protected function prepareConversationClosure($next, Conversation $conversation, array $parameters)
    {
        if ($next instanceof SerializableClosure) {
            $next = $next->getClosure()->bindTo($conversation, $conversation);
        } elseif ($next instanceof Closure) {
            $next = $next->bindTo($conversation, $conversation);
        }

        $parameters[] = $conversation;
        call_user_func_array($next, $parameters);
    }
}

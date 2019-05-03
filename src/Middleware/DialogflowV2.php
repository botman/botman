<?php

namespace BotMan\BotMan\Middleware;

use BotMan\BotMan\BotMan;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Google\Cloud\Dialogflow\V2\SessionsClient;
use BotMan\BotMan\Interfaces\MiddlewareInterface;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

/**
 * Dialogflow api V2
 * composer require google/cloud-dialogflow
 * environment variables to add to your .env file:
 * GOOGLE_CLOUD_PROJECT=project-id
 * GOOGLE_APPLICATION_CREDENTIALS=/path/to/project-secret.json
 * get your application credentials file from the google cloud console (Service account)
 * Drop-in replacement for the v1 Middleware.
 */
class DialogflowV2 implements MiddlewareInterface
{
    /** @var string */
    protected $lang = 'en';

    /**
     * constructor.
     * @param string $lang language
     */
    public function __construct($lang = 'en')
    {
        $this->lang = $lang;
    }

    /**
     * Create a new Dialogflow middleware instance.
     * @param string $lang language
     * @return DialogflowV2
     */
    public static function create($lang = 'en')
    {
        return new static($lang);
    }

    /**
     * Restrict the middleware to only listen for dialogflow actions.
     * @param  bool $listen
     * @return $this
     */
    public function listenForAction($listen = true)
    {
        $this->listenForAction = $listen;

        return $this;
    }

    /**
     * Perform the Dialogflow API call and cache it for the message.
     * @param  \BotMan\BotMan\Messages\Incoming\IncomingMessage $message
     * @return \stdClass
     */
    protected function getResponse(IncomingMessage $message)
    {
        $this->response = $this->detectIntentTexts(
            $message->getText(),
            md5($message->getConversationIdentifier()),
            $this->lang
        );

        return $this->response;
    }

    private function detectIntentTexts($text, $sessionId, $languageCode = 'en')
    {
        $sessionsClient = new SessionsClient();
        $session = $sessionsClient->sessionName(env('GOOGLE_CLOUD_PROJECT'), $sessionId ?: uniqid());

        // create text input
        $textInput = new TextInput();
        $textInput->setText($text);
        $textInput->setLanguageCode($languageCode);

        // create query input
        $queryInput = new QueryInput();
        $queryInput->setText($textInput);

        // get response and relevant info
        $response = $sessionsClient->detectIntent($session, $queryInput);

        $sessionsClient->close();

        return $response;
    }

    /**
     * Handle a captured message.
     *
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $message
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
        $response = $this->getResponse($message);
        $queryResult = $response->getQueryResult();
        $intent = $queryResult->getIntent();

        $parameters = [];
        foreach ($queryResult->getParameters()->getFields() as $name => $field) {
            $parameters[$name] = $field->getStringValue();
        }

        $contexts = [];
        foreach ($queryResult->getOutputContexts() as $context) {
            $cparams = [];
            foreach ($context->getParameters()->getFields() as $name => $field) {
                $cparams[$name] = $field->getStringValue();
            }
            $contexts[] = [
                'name' => substr(strrchr($context->getName(), '/'), 1),
                'parameters' => $cparams,
                'lifespan' => $context->getLifespanCount(),
            ];
        }

        $message->addExtras('apiReply', $queryResult->getFulfillmentText());
        $message->addExtras('apiAction', $queryResult->getAction());
        $message->addExtras('apiActionIncomplete', ! $queryResult->getAllRequiredParamsPresent());
        $message->addExtras('apiIntent', $intent->getDisplayName());
        $message->addExtras('apiParameters', $parameters);
        $message->addExtras('apiContexts', $contexts);

        return $next($message);
    }

    /**
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $message
     * @param string $pattern
     * @param bool $regexMatched Indicator if the regular expression was matched too
     * @return bool
     */
    public function matching(IncomingMessage $message, $pattern, $regexMatched)
    {
        if ($this->listenForAction) {
            $pattern = '/^'.$pattern.'$/i';

            return (bool) preg_match($pattern, $message->getExtras()['apiAction']);
        }

        return true;
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
        return $next($payload);
    }
}

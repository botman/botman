<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\Message;
use Illuminate\Support\Collection;

class FacebookOptinDriver extends FacebookDriver
{
    const DRIVER_NAME = 'FacebookOptin';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        $validSignature = ! $this->config->has('facebook_app_secret') || $this->validateSignature();
        $messages = Collection::make($this->event->get('messaging'))->filter(function ($msg) {
            return isset($msg['optin']['ref']) && isset($msg['optin']['user_ref']);
        });

        return ! $messages->isEmpty() && $validSignature;
    }

    /**
     * Retrieve the optin message.
     *
     * @return array
     */
    public function getMessages()
    {
        $messages = Collection::make($this->event->get('messaging'));
        $messages = $messages->transform(function ($msg) {
            return new Message($msg['optin']['ref'], $msg['recipient']['id'], $msg['optin']['user_ref'], $msg);
        })->toArray();

        if (count($messages) === 0) {
            return [new Message('', '', '')];
        }

        return $messages;
    }

    /**
     * @param string $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function reply($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = array_merge_recursive([
            'recipient' => [
                'user_ref' => $matchingMessage->getChannel(),
            ],
            'message' => [
                'text' => $message,
            ],
        ], $additionalParameters);

        $parameters['access_token'] = $this->config->get('facebook_token');

        return $this->http->post('https://graph.facebook.com/v2.6/me/messages', [], $parameters);
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}

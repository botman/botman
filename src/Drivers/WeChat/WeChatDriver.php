<?php

namespace Mpociot\BotMan\Drivers\WeChat;

use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Drivers\Driver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mpociot\BotMan\Messages\Message as IncomingMessage;

class WeChatDriver extends Driver
{
    const DRIVER_NAME = 'WeChat';

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        try {
            $xml = @simplexml_load_string($request->getContent(), 'SimpleXMLElement', LIBXML_NOCDATA);
            $json = json_encode($xml);
            $data = json_decode($json, true);
        } catch (\Exception $e) {
            $data = [];
        }
        $this->payload = $request->request->all();
        $this->event = Collection::make($data);
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return ! is_null($this->event->get('MsgType')) && ! is_null($this->event->get('MsgId')) && $this->event->get('MsgType') === 'text';
    }

    /**
     * @param  Message $message
     * @return Answer
     */
    public function getConversationAnswer(Message $message)
    {
        return Answer::create($message->getText())->setMessage($message);
    }

    /**
     * @param Message $matchingMessage
     * @return User
     */
    public function getUser(Message $matchingMessage)
    {
        $response = $this->http->post('https://api.wechat.com/cgi-bin/user/info?access_token='.$this->getAccessToken().'&openid='.$matchingMessage->getRecipient().'&lang=en_US',
            [], [], [], true);
        $responseData = json_decode($response->getContent());
        $nickname = isset($responseData->nickname) ? $responseData->nickname : '';

        return new User($matchingMessage->getSender(), null, null, $nickname);
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        return [
            new Message($this->event->get('Content'), $this->event->get('FromUserName'),
                $this->event->get('ToUserName'), $this->event),
        ];
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return false;
    }

    /**
     * @return string
     */
    protected function getAccessToken()
    {
        $response = $this->http->post('https://api.wechat.com/cgi-bin/token?grant_type=client_credential&appid='.$this->config->get('wechat_app_id').'&secret='.$this->config->get('wechat_app_key'),
            [], []);
        $responseData = json_decode($response->getContent());

        return $responseData->access_token;
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return Response
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = array_merge_recursive([
            'touser' => $matchingMessage->getSender(),
            'msgtype' => 'text',
        ], $additionalParameters);

        if ($message instanceof Question) {
            $parameters['text'] = [
                'content' => $message->getText(),
            ];
        } elseif ($message instanceof IncomingMessage) {
            $parameters['msgtype'] = 'news';

            $attachment = $message->getAttachment();
            if (! is_null($attachment)) {
                $article = [
                    'title' => $message->getText(),
                    'picurl' => $attachment->getUrl(),
                ];
            } else {
                $article = [
                    'title' => $message->getText(),
                    'picurl' => null,
                ];
            }

            $parameters['news'] = [
                'articles' => [$article],
            ];
        } else {
            $parameters['text'] = [
                'content' => $message,
            ];
        }

        return $parameters;
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        Response::create('')->send();

        return $this->http->post('https://api.wechat.com/cgi-bin/message/custom/send?access_token='.$this->getAccessToken(),
            [], $payload, [], true);
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return ! is_null($this->config->get('wechat_app_id')) && ! is_null($this->config->get('wechat_app_key'));
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param Message $matchingMessage
     * @return Response
     */
    public function sendRequest($endpoint, array $parameters, Message $matchingMessage)
    {
        return $this->http->post('https://api.wechat.com/cgi-bin/'.$endpoint.'?access_token='.$this->getAccessToken(),
            [], $parameters, [], true);
    }
}

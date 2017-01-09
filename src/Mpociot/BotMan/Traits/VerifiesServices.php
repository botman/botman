<?php

namespace Mpociot\BotMan\Traits;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait VerifiesServices
{
    /**
     * Verify service webhook URLs.
     *
     * @param array $services The services and verification codes.
     * @return Response|null
     */
    public function verifyServices($services = null, $weChatVerification = null)
    {
        // fallback
        if (!is_array($services)) {
            $services = ['facebook' => $services];
        }
        $services['we_chat'] = $wheChatVerification;

        foreach ($services as $service => $code) {
            $method = camel_case("verify_{$service}_service");

            $response = $this->$method($code);

            if (! is_null($response)) {
                return $response;
            }
        }
    }

    /**
     * Verify Facebook's service webhook URLs.
     *
     * @param string $facebookVerification The Facebook verification code.
     * @return Response|null
     */
    public function verifyFacebookService($facebookVerification = null)
    {
        $request = (isset($this->request)) ? $this->request : Request::createFromGlobals();

        // Facebook verification
        if ($request->get('hub_mode') === 'subscribe' && $request->get('hub_verify_token') === $facebookVerification) {
            return Response::create($request->get('hub_challenge'))->send();
        }
    }

    /**
     * Verify Facebook's service webhook URLs.
     *
     * @param string $slackVerification The Slack verification code.
     * @return Response|null
     */
    public function verifySlackService($slackVerification = null)
    {
        $request = (isset($this->request)) ? $this->request : Request::createFromGlobals();
        $payload = Collection::make(json_decode($request->getContent(), true));

        // Slack verification
        if ($payload->get('type') === 'url_verification' && $payload->get('token') == $slackVerification) {
            return Response::create($payload->get('challenge'))->send();
        }
    }

    /**
     * Verify WeChat's service webhook URLs.
     *
     * @param string $weChatVerification The WeChat verification code.
     * @return Response|null
     */
    public function verifyWeChatService($weChatVerification = null)
    {
        $request = (isset($this->request)) ? $this->request : Request::createFromGlobals();

        // WeChat verification
        if ($request->get('signature') !== null && $request->get('timestamp') !== null && $request->get('nonce') !== null && $request->get('echostr') !== null) {
            $tmpArr = [$weChatVerification, $request->get('timestamp'), $request->get('nonce')];
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode($tmpArr);
            $tmpStr = sha1($tmpStr);

            if ($tmpStr == $request->get('signature')) {
                return Response::create($request->get('echostr'))->send();
            }
        }
    }
}

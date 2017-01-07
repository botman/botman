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
     * @param string $facebookVerification The Facebook verification string to match
     * @param string $weChatVerification The WeChat verification token to match
     * @return Response
     */
    public function verifyServices($facebookVerification = null, $weChatVerification = null)
    {
        $request = (isset($this->request)) ? $this->request : Request::createFromGlobals();
        $payload = Collection::make(json_decode($request->getContent(), true));

        // Slack verification
        if ($payload->get('type') === 'url_verification') {
            return Response::create($payload->get('challenge'))->send();
        }

        // Facebook verification
        if ($request->get('hub_mode') === 'subscribe' && $request->get('hub_verify_token') === $facebookVerification) {
            return Response::create($request->get('hub_challenge'))->send();
        }

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

<?php

namespace Mpociot\BotMan;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait VerifiesServices
{

    /**
     * Verify service webhook URLs
     *
     * @param string $facebookVerification the Facebook verification string to match
     * @return Response
     */
    public function verifyServices($facebookVerification)
    {
        $request = Request::createFromGlobals();

        /** @var \Symfony\Component\HttpFoundation\ParameterBag $payload */
        $payload = $request->json();

        // Slack verification
        if ($payload->get('type') === 'url_verification') {
            return Response::create($payload->get('challenge'))->send();
        }

        // Facebook verification
        if ($request->get('hub_mode') === 'subscribe' && $request->get('hub_verify_token') === $facebookVerification) {
            return Response::create($request->get('hub_challenge'))->send();
        }
    }

}
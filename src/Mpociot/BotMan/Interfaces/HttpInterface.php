<?php

namespace Mpociot\BotMan\Interfaces;

use Symfony\Component\HttpFoundation\Response;

interface HttpInterface
{
    /**
     * Send a post request to a URL.
     *
     * @param  string $url
     * @param  array  $urlParameters
     * @param  array  $postParameters
     * @param  array  $headers
     * @return Response
     */
    public function post($url, array $urlParameters = [], array $postParameters = [], array $headers = []);
}

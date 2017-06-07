<?php

namespace BotMan\BotMan\Interfaces;

use Symfony\Component\HttpFoundation\Response;

interface HttpInterface
{
    /**
     * Send a post request to a URL.
     *
     * @param  string $url
     * @param  array $urlParameters
     * @param  array $postParameters
     * @param  array $headers
     * @param  bool $asJSON
     * @return Response
     */
    public function post(
        $url,
        array $urlParameters = [],
        array $postParameters = [],
        array $headers = [],
        $asJSON = false
    );

    /**
     * Send a get request to a URL.
     *
     * @param  string $url
     * @param  array $urlParameters
     * @param  array $headers
     * @param  bool $asJSON
     * @return Response
     */
    public function get($url, array $urlParameters = [], array $headers = [], $asJSON = false);
}

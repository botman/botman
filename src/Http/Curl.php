<?php

namespace BotMan\BotMan\Http;

use BotMan\BotMan\Interfaces\HttpInterface;
use Symfony\Component\HttpFoundation\Response;

class Curl implements HttpInterface
{
    /**
     * {@inheritdoc}
     */
    public function post(
        $url,
        array $urlParameters = [],
        array $postParameters = [],
        array $headers = [],
        $asJSON = false
    ) {
        $request = $this->prepareRequest($url, $urlParameters, $headers);

        curl_setopt($request, CURLOPT_POST, count($postParameters));
        if ($asJSON === true) {
            curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($postParameters));
        } else {
            curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query($postParameters));
        }

        return $this->executeRequest($request);
    }

    /**
     * Send a get request to a URL.
     *
     * @param  string $url
     * @param  array $urlParameters
     * @param  array $headers
     * @param  bool $asJSON
     * @return Response
     */
    public function get($url, array $urlParameters = [], array $headers = [], $asJSON = false)
    {
        $request = $this->prepareRequest($url, $urlParameters, $headers);

        return $this->executeRequest($request);
    }

    /**
     * Prepares a request using curl.
     *
     * @param  string $url [description]
     * @param  array $parameters [description]
     * @param  array $headers [description]
     * @return resource
     */
    protected static function prepareRequest($url, $parameters = [], $headers = [])
    {
        $request = curl_init();

        if ($query = http_build_query($parameters)) {
            $url .= '?'.$query;
        }

        curl_setopt($request, CURLOPT_URL, $url);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($request, CURLINFO_HEADER_OUT, true);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, true);

        return $request;
    }

    /**
     * Executes a curl request.
     *
     * @param  resource $request
     * @return Response
     */
    public function executeRequest($request)
    {
        $body = curl_exec($request);
        $info = curl_getinfo($request);

        curl_close($request);

        $statusCode = $info['http_code'] === 0 ? 500 : $info['http_code'];

        return new Response((string) $body, $statusCode, []);
    }
}

<?php

namespace BotMan\BotMan\Http;

use BotMan\BotMan\Interfaces\HttpInterface;
use Symfony\Component\HttpFoundation\Response;

class Curl implements HttpInterface
{
    protected $options;

    public function __construct($options = [])
    {
        $this->options = $options;
    }

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
     * Send a put request to a URL
     *
     * @param  string $url
     * @param  array $urlParameters
     * @param  array $headers
     * @param  bool $asJSON
     * @return void
     */
    public function put(
        $url,
        array $urlParameters = [],
        array $postParameters = [],
        array $headers = [],
        $asJSON = false
    ) {
        $request = $this->prepareRequest($url, $urlParameters, $headers);

        curl_setopt($request, CURLOPT_PUT, true);

        if ($asJSON === true) {
            curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($postParameters));
        } else {
            curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query($postParameters));
        }

        return $this->executeRequest($request);
    }

    /**
     * Send a delete request to a URL.
     *
     * @param  string $url
     * @param  array $urlParameters
     * @param  array $headers
     * @param  bool $asJSON
     * @return Response
     */
    public function delete($url, array $urlParameters = [], array $headers = [], $asJSON = false)
    {
        $request = $this->prepareRequest($url, $urlParameters, $headers);

        curl_setopt($request, CURLOPT_CUSTOMREQUEST, 'DELETE');

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
    protected function prepareRequest($url, $parameters = [], $headers = [])
    {
        $request = curl_init();

        if ($query = http_build_query($parameters)) {
            $url .= '?' . $query;
        }

        curl_setopt($request, CURLOPT_URL, $url);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($request, CURLINFO_HEADER_OUT, true);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($request, CURLOPT_VERBOSE, false);

        if (!empty($this->options)) {
            curl_setopt_array($request, $this->options);
        }

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

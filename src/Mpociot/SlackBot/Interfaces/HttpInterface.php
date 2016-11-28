<?php
/**
 * Created by PhpStorm.
 * User: marcel
 * Date: 27/11/2016
 * Time: 23:16
 */

namespace Mpociot\SlackBot\Interfaces;


interface HttpInterface
{
    /**
     * Send a post request to a URL.
     *
     * @param  string $url
     * @param  array  $urlParameters
     * @param  array  $postParameters
     * @param  array  $headers
     * @return \Frlnc\Slack\Contracts\Http\Response
     */
    public function post($url, array $urlParameters = [], array $postParameters = [], array $headers = []);
}
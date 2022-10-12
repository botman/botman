<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conversation Cache Time
    |--------------------------------------------------------------------------
    |
    | BotMan caches each started conversation. This value defines the
    | number of minutes that a conversation will remain stored in
    | the cache.
    |
    */
    'conversation_cache_time' => 40,

    /*
    |--------------------------------------------------------------------------
    | User Cache Time
    |--------------------------------------------------------------------------
    |
    | BotMan caches user information of the incoming messages.
    | This value defines the number of minutes that this
    | data will remain stored in the cache.
    |
    */
    'user_cache_time' => 30,

    /*
    |--------------------------------------------------------------------------
    | cURL Options
    |--------------------------------------------------------------------------
    |
    | BotMan will use this array to prepare every cURL request.
    | The same option can be set per driver in the relative
    | driver configuration file. It uses the
    | curl_setopt_array() function.
    |
    */
    'curl_options' => [],

];

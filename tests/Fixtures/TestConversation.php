<?php

namespace Mpociot\BotMan\Tests\Fixtures;

use Mpociot\BotMan\Conversation;

class TestConversation extends Conversation
{
    /**
     * @return mixed
     */
    public function run()
    {
    }

    protected function _throwException($message)
    {
        throw new \Exception($message);
    }
}

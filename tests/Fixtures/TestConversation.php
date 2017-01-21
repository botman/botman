<?php

namespace Mpociot\BotMan\Tests\Fixtures;

use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Conversation;

class TestConversation extends Conversation
{
    /**
     * @return mixed
     */
    public function run()
    {
        $this->ask('This is a test question', function (Answer $answer) {
            if ($answer->getText() === 'repeat') {
                $this->repeat();
            }
        });
    }

    protected function _throwException($message)
    {
        throw new \Exception($message);
    }
}

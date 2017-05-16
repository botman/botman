<?php

namespace Mpociot\BotMan\Tests\Fixtures;

use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
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
            if ($answer->getText() === 'repeat_modified') {
                $this->repeat('This is a modified test question');
            }
        });
    }

    public function skipsConversation(Message $message)
    {
        return $message->getText() === 'skip_keyword';
    }

    public function stopsConversation(Message $message)
    {
        return $message->getText() === 'stop_keyword';
    }

    protected function _throwException($message)
    {
        throw new \Exception($message);
    }
}

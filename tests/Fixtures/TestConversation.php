<?php

namespace BotMan\BotMan\Tests\Fixtures;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

class TestConversation extends Conversation
{
    protected $cacheTime = 900;

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

    public function skipsConversation(IncomingMessage $message)
    {
        return $message->getText() === 'skip_keyword';
    }

    public function stopsConversation(IncomingMessage $message)
    {
        return $message->getText() === 'stop_keyword';
    }

    protected function _throwException($message)
    {
        throw new \Exception($message);
    }
}

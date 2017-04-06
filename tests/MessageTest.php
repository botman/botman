<?php

namespace Mpociot\BotMan\Tests;

use Mpociot\BotMan\Message;
use PHPUnit_Framework_TestCase;

class MessageTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $message = new Message('', '', '');
        $this->assertInstanceOf(Message::class, $message);
    }

    /** @test */
    public function it_can_return_the_channel()
    {
        $message = new Message('', '', 'channel');
        $this->assertSame('channel', $message->getChannel());
    }

    /** @test */
    public function it_can_return_the_user()
    {
        $message = new Message('', 'user', '');
        $this->assertSame('user', $message->getUser());
    }

    /** @test */
    public function it_can_return_the_message()
    {
        $message = new Message('my message', '', '');
        $this->assertSame('my message', $message->getMessage());
    }

    /** @test */
    public function it_can_return_the_conversation_identifier()
    {
        $message = new Message('', 'user', 'channel');
        $identifier = 'conversation-'.sha1('user').'-'.sha1('channel');
        $this->assertSame($identifier, $message->getConversationIdentifier());
    }

    /** @test */
    public function it_can_return_the_originated_conversation_identifier()
    {
        $message = new Message('', 'user', 'channel');
        $identifier = 'conversation-da39a3ee5e6b4b0d3255bfef95601890afd80709-'.sha1('channel');
        $this->assertSame($identifier, $message->getOriginatedConversationIdentifier());
    }

    /** @test */
    public function it_can_return_the_payload()
    {
        $message = new Message('', '', '', 'payload');
        $this->assertSame('payload', $message->getPayload());

        $message = new Message('', '', '', ['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $message->getPayload());
    }

    /** @test */
    public function it_can_set_and_return_an_image()
    {
        $message = new Message('', '', '');
        $this->assertSame([], $message->getImages());

        $message->setImages(['foo']);
        $this->assertSame(['foo'], $message->getImages());
    }

    /** @test */
    public function it_can_set_and_return_extras()
    {
        $message = new Message('', '', '');
        $this->assertSame([], $message->getExtras());

        $message->addExtras('intents', [1, 2, 3]);
        $this->assertSame([
            'intents' => [1, 2, 3],
        ], $message->getExtras());
    }

    /** @test */
    public function it_can_set_and_return_single_extra()
    {
        $message = new Message('', '', '');
        $message->addExtras('intents', [1, 2, 3]);
        $this->assertSame([1, 2, 3], $message->getExtras('intents'));

        $this->assertNull($message->getExtras('not-set'));
    }
}

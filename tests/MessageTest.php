<?php

namespace BotMan\BotMan\tests;

use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $message = new IncomingMessage('', '', '');
        $this->assertInstanceOf(IncomingMessage::class, $message);
    }

    /** @test */
    public function it_can_return_the_channel()
    {
        $message = new IncomingMessage('', '', 'channel');
        $this->assertSame('channel', $message->getRecipient());
    }

    /** @test */
    public function it_can_return_the_user()
    {
        $message = new IncomingMessage('', 'user', '');
        $this->assertSame('user', $message->getSender());
    }

    /** @test */
    public function it_can_return_the_message()
    {
        $message = new IncomingMessage('my message', '', '');
        $this->assertSame('my message', $message->getText());
    }

    /** @test */
    public function it_can_return_the_conversation_identifier()
    {
        $message = new IncomingMessage('', 'user', 'channel');
        $identifier = 'conversation-'.sha1('user').'-'.sha1('channel');
        $this->assertSame($identifier, $message->getConversationIdentifier());
    }

    /** @test */
    public function it_can_return_the_originated_conversation_identifier()
    {
        $message = new IncomingMessage('', 'user', 'channel');
        $identifier = 'conversation-'.sha1('user').'-'.sha1('');
        $this->assertSame($identifier, $message->getOriginatedConversationIdentifier());
    }

    /** @test */
    public function it_can_return_the_payload()
    {
        $message = new IncomingMessage('', '', '', 'payload');
        $this->assertSame('payload', $message->getPayload());

        $message = new IncomingMessage('', '', '', ['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $message->getPayload());
    }

    /** @test */
    public function it_can_set_and_return_an_image()
    {
        $message = new IncomingMessage('', '', '');
        $this->assertSame([], $message->getImages());

        $message->setImages(['foo']);
        $this->assertSame(['foo'], $message->getImages());
    }

    /** @test */
    public function it_can_set_and_return_extras()
    {
        $message = new IncomingMessage('', '', '');
        $this->assertSame([], $message->getExtras());

        $message->addExtras('intents', [1, 2, 3]);
        $this->assertSame([
            'intents' => [1, 2, 3],
        ], $message->getExtras());
    }

    /** @test */
    public function it_can_set_and_return_single_extra()
    {
        $message = new IncomingMessage('', '', '');
        $message->addExtras('intents', [1, 2, 3]);
        $this->assertSame([1, 2, 3], $message->getExtras('intents'));

        $this->assertNull($message->getExtras('not-set'));
    }
}

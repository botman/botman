<?php

namespace Mpociot\BotMan\Tests\Messages;

use Mpociot\BotMan\Messages\Message;
use PHPUnit_Framework_TestCase;

class MessageTest extends PHPUnit_Framework_TestCase
{

    /** @test */
    public function it_can_be_created()
    {
        $message = Message::create('foo', 'bar');
        $this->assertSame('foo', $message->getMessage());
        $this->assertSame('bar', $message->getImage());

        $message = new Message('foo', 'bar');
        $this->assertSame('foo', $message->getMessage());
        $this->assertSame('bar', $message->getImage());
    }

    /** @test */
    public function it_can_set_a_message()
    {
        $message = Message::create()->message('foo');
        $this->assertSame('foo', $message->getMessage());
    }

    /** @test */
    public function it_can_set_an_image()
    {
        $message = Message::create()->image('foo');
        $this->assertSame('foo', $message->getImage());
    }

}
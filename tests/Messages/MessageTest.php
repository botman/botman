<?php

namespace Mpociot\BotMan\Tests\Messages;

use Mpociot\BotMan\Attachments\Image;
use Mpociot\BotMan\Attachments\Video;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Messages\Message;

class MessageTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $message = Message::create('foo', 'bar');
        $this->assertSame('foo', $message->getText());
        $this->assertSame('bar', $message->getAttachment());

        $message = new Message('foo', 'bar');
        $this->assertSame('foo', $message->getText());
        $this->assertSame('bar', $message->getAttachment());
    }

    /** @test */
    public function it_can_set_a_message()
    {
        $message = Message::create()->text('foo');
        $this->assertSame('foo', $message->getText());
    }

    /** @test */
    public function it_can_set_an_image()
    {
        $message = Message::create()->withAttachment(Image::url('foo'));
        $this->assertSame('foo', $message->getAttachment()->getUrl());
    }

    /** @test */
    public function it_can_set_a_videoimage()
    {
	    $message = Message::create()->withAttachment(Video::url('foo'));
	    $this->assertSame('foo', $message->getAttachment()->getUrl());
    }
}

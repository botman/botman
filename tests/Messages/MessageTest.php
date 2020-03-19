<?php

namespace BotMan\BotMan\tests\Messages;

use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $attachment = new Image('foo');
        $message = OutgoingMessage::create('foo', $attachment);
        $this->assertSame('foo', $message->getText());
        $this->assertSame($attachment, $message->getAttachment());

        $message = new OutgoingMessage('foo', $attachment);
        $this->assertSame('foo', $message->getText());
        $this->assertSame($attachment, $message->getAttachment());
    }

    /** @test */
    public function it_can_set_a_message()
    {
        $message = OutgoingMessage::create()->text('foo');
        $this->assertSame('foo', $message->getText());
    }

    /** @test */
    public function it_can_set_an_image()
    {
        $message = OutgoingMessage::create()->withAttachment(Image::url('foo'));
        $this->assertSame('foo', $message->getAttachment()->getUrl());
    }

    /** @test */
    public function it_can_set_an_image_title()
    {
        $message = OutgoingMessage::create()->withAttachment(Image::url('foo')->title('title'));
        $this->assertSame('foo', $message->getAttachment()->getUrl());
        $this->assertSame('title', $message->getAttachment()->getTitle());
    }

    /** @test */
    public function it_can_set_a_videoimage()
    {
        $message = OutgoingMessage::create()->withAttachment(Video::url('foo'));
        $this->assertSame('foo', $message->getAttachment()->getUrl());
    }

    /** @test */
    public function it_can_be_translated()
    {
        $translationCallable = function ($text) {
            return strtoupper($text);
        };
        $message = OutgoingMessage::create()->text('foo');
        $message->translate($translationCallable);
        $this->assertSame('FOO', $message->getText());
    }

    /** @test */
    public function it_can_translate_attachement()
    {
        $translationCallable = function ($text) {
            return strtoupper($text);
        };
        $message = OutgoingMessage::create()->withAttachment(Image::url('foo')->title('bar'));
        $message->translate($translationCallable);

        $this->assertSame('BAR', $message->getAttachment()->getTitle());
    }

    /** @test */
    public function it_can_be_translated_once_only()
    {
        $translationCallable = function ($text) {
            return strrev($text);
        };
        $message = OutgoingMessage::create()->text('foo');
        $message->translate($translationCallable);
        $this->assertSame('oof', $message->getText());

        $message->translate($translationCallable);
        $this->assertSame('oof', $message->getText());

    }

    /** @test */
    public function it_can_translate_attachement_once_only()
    {
        $translationCallable = function ($text) {
            return strrev($text);
        };
        $message = OutgoingMessage::create()->withAttachment(Image::url('foo')->title('bar'));
        $message->translate($translationCallable);
        $this->assertSame('rab', $message->getAttachment()->getTitle());

        $message->translate($translationCallable);
        $this->assertSame('rab', $message->getAttachment()->getTitle());

    }
}

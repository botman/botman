<?php
namespace Mpociot\SlackBot\Tests;

use Mpociot\SlackBot\Answer;
use PHPUnit_Framework_TestCase;

class AnswerTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $answer = Answer::create('text');
        $this->assertSame('text', $answer->getText());
    }

    /** @test */
    public function it_can_be_created_using_new()
    {
        $answer = new Answer('text');
        $this->assertSame('text', $answer->getText());
    }

    /** @test */
    public function it_can_set_a_text()
    {
        $answer = new Answer('text');
        $answer->setText('foo');
        $this->assertSame('foo', $answer->getText());
    }

    /** @test */
    public function it_can_set_a_value()
    {
        $answer = new Answer();
        $answer->setValue('foo');
        $this->assertSame('foo', $answer->getValue());
    }

    /** @test */
    public function it_can_set_a_callback_id()
    {
        $answer = new Answer();
        $answer->setCallbackId('foo');
        $this->assertSame('foo', $answer->getCallbackId());
    }

    /** @test */
    public function it_detects_if_its_from_an_interactive_message()
    {
        $answer = new Answer();
        $this->assertFalse($answer->isInteractiveMessageReply());

        $answer = Answer::create()->setCallbackId('12345');
        $this->assertTrue($answer->isInteractiveMessageReply());
    }

    /** @test */
    public function it_returns_text_as_string()
    {
        $answer = new Answer('foo');
        $this->assertSame('foo', (string)$answer);
    }

}
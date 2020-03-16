<?php

namespace BotMan\BotMan\tests\Messages;

use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use PHPUnit\Framework\TestCase;

class QuestionTest extends TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $message = Question::create('foo');
        $this->assertSame('foo', $message->getText());
        $this->assertSame([], $message->getActions());

        $message = new Question('foo');
        $this->assertSame('foo', $message->getText());
        $this->assertSame([], $message->getActions());
    }

    /** @test */
    public function it_can_add_an_action()
    {
        $button = Button::create('bar');
        $message = Question::create('foo')->addAction($button);
        $this->assertSame([$button->toArray()], $message->getActions());
    }

    /** @test */
    public function it_can_add_a_button()
    {
        $button = Button::create('bar');
        $message = Question::create('foo')->addButton($button);
        $this->assertSame([$button->toArray()], $message->getButtons());
    }

    /** @test */
    public function it_can_add_buttons()
    {
        $barButton = Button::create('bar');
        $bazButton = Button::create('baz');
        $message = Question::create('foo')->addButtons([$barButton, $bazButton]);
        $this->assertSame([$barButton->toArray(), $bazButton->toArray()], $message->getButtons());
    }

    /** @test */
    public function it_can_add_a_callback_id()
    {
        $message = Question::create('foo')->callbackId('callback');
        $this->assertArraySubset(['callback_id' => 'callback'], $message->toArray());
    }

    /** @test */
    public function it_can_add_a_fallback()
    {
        $message = Question::create('foo')->fallback('fallback');
        $this->assertArraySubset(['fallback' => 'fallback'], $message->toArray());
    }

    /** @test */
    public function it_can_be_serialized()
    {
        $message = Question::create('foo');
        $this->assertSame('{"text":"foo","fallback":null,"callback_id":null,"actions":[]}', json_encode($message));
    }

    /** @test */
    public function it_can_return_a_web_accessible_array()
    {
        $message = Question::create('foo');
        $this->assertSame([
            'type' => 'text',
            'text' => 'foo',
            'fallback' => null,
            'callback_id' => null,
            'actions' => [],
        ], $message->toWebDriver());
    }

    /** @test */
    public function it_can_return_a_web_accessible_array_with_actions()
    {
        $button = Button::create('bar');
        $message = Question::create('foo')->addAction($button);
        $this->assertSame([
            'type' => 'actions',
            'text' => 'foo',
            'fallback' => null,
            'callback_id' => null,
            'actions' => [$button->toArray()],
        ], $message->toWebDriver());
    }

    /** @test */
    public function it_can_be_translated()
    {
        $translationCallable = function ($text) {
            return strtoupper($text);
        };
        $message = Question::create("foo");
        $message->translate($translationCallable);
        $this->assertSame([
            'type' => 'text',
            'text' => 'FOO',
            'fallback' => null,
            'callback_id' => null,
            'actions' => [],
        ], $message->toWebDriver());
    }

    /** @test */
    public function it_can_translate_an_action()
    {
        $translationCallable = function ($text) {
            return strtoupper($text);
        };
        $button = Button::create('qux')->name('bar');
        $message = Question::create("foo")->addAction($button);
        $message->translate($translationCallable);
        $this->assertSame([
            'type' => 'actions',
            'text' => 'FOO',
            'fallback' => null,
            'callback_id' => null,
            'actions' => [[
                'name' => 'BAR',
                'text' => 'QUX',
                'image_url' => null,
                'type' => 'button',
                'value' => null,
                'additional' => [],
            ]],
        ], $message->toWebDriver());
    }

    /** @test */
    public function it_can_translate_a_button()
    {
        $translationCallable = function ($text) {
            return strtoupper($text);
        };
        $button = Button::create('qux');
        $message = Question::create("foo")->addButton($button);
        $message->translate($translationCallable);
        $this->assertSame([
            'type' => 'actions',
            'text' => 'FOO',
            'fallback' => null,
            'callback_id' => null,
            'actions' => [[
                'name' => 'QUX',
                'text' => 'QUX',
                'image_url' => null,
                'type' => 'button',
                'value' => null,
                'additional' => [],
            ]],
        ], $message->toWebDriver());
    }

    /** @test */
    public function it_can_translate_buttons()
    {
        $translationCallable = function ($text) {
            return strtoupper($text);
        };
        $button1 = Button::create('bar');
        $button2 = Button::create('qux');
        $message = Question::create("foo")->addButtons([$button1, $button2]);
        $message->translate($translationCallable);
        $this->assertSame([
            'type' => 'actions',
            'text' => 'FOO',
            'fallback' => null,
            'callback_id' => null,
            'actions' => [
                [
                    'name' => 'BAR',
                    'text' => 'BAR',
                    'image_url' => null,
                    'type' => 'button',
                    'value' => null,
                    'additional' => [],
                ],
                [
                    'name' => 'QUX',
                    'text' => 'QUX',
                    'image_url' => null,
                    'type' => 'button',
                    'value' => null,
                    'additional' => [],
                ],
            ],
        ], $message->toWebDriver());
    }
}

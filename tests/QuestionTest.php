<?php

namespace BotMan\BotMan\tests;

use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;

class QuestionTest extends TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $question = Question::create('text');
        $this->assertSame('text', Arr::get($question->toArray(), 'text'));
    }

    /** @test */
    public function it_can_be_created_using_new()
    {
        $question = new Question('text');
        $this->assertSame('text', Arr::get($question->toArray(), 'text'));
    }

    /** @test */
    public function it_can_have_a_fallback_text()
    {
        $question = Question::create('text')->fallback('fallback text');
        $this->assertSame('fallback text', Arr::get($question->toArray(), 'fallback'));
    }

    /** @test */
    public function it_can_have_a_callback_id()
    {
        $question = Question::create('text')->callbackId('my_callback_id');
        $this->assertSame('my_callback_id', Arr::get($question->toArray(), 'callback_id'));
    }

    /** @test */
    public function it_can_have_buttons()
    {
        $question = Question::create('text');
        $question->addButton(Button::create('button 1'));
        $this->assertCount(1, Arr::get($question->toArray(), 'actions'));
        $this->assertSame('button 1', Arr::get($question->toArray(), 'actions.0.text'));
    }

    /** @test */
    public function it_can_add_multiple_buttons()
    {
        $question = Question::create('text');
        $question->addButtons([
            Button::create('button 1'),
            Button::create('button 2'),
        ]);
        $this->assertCount(2, Arr::get($question->toArray(), 'actions'));
        $this->assertSame('button 1', Arr::get($question->toArray(), 'actions.0.text'));
        $this->assertSame('button 2', Arr::get($question->toArray(), 'actions.1.text'));
    }

    /** @test */
    public function it_can_be_json_encoded()
    {
        $question = Question::create('text');
        $question->addButton(Button::create('button 1'));

        $this->assertSame(json_encode($question->toArray()), json_encode($question));
    }
}

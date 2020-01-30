<?php

namespace BotMan\BotMan\tests;

use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;

class ButtonTest extends TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $button = Button::create('text');
        $this->assertSame('text', Arr::get($button->toArray(), 'text'));
    }

    /** @test */
    public function it_can_be_created_using_new()
    {
        $button = new Button('text');
        $this->assertSame('text', Arr::get($button->toArray(), 'text'));
    }

    /** @test */
    public function it_can_have_a_value()
    {
        $button = Button::create('text')->value('value');
        $this->assertSame('value', Arr::get($button->toArray(), 'value'));
    }

    /** @test */
    public function it_defaults_name_to_text()
    {
        $button = Button::create('text');
        $this->assertSame('text', Arr::get($button->toArray(), 'name'));
    }

    /** @test */
    public function it_can_have_a_name()
    {
        $button = Button::create('text')->name('name');
        $this->assertSame('text', Arr::get($button->toArray(), 'text'));
        $this->assertSame('name', Arr::get($button->toArray(), 'name'));
    }

    /** @test */
    public function it_can_have_an_image_url()
    {
        $button = Button::create('text')->image('http://foobar.com/image.png');
        $this->assertSame('http://foobar.com/image.png', Arr::get($button->toArray(), 'image_url'));
    }

    /** @test */
    public function it_can_have_additional_parameters()
    {
        $button = Button::create('text')->additionalParameters([
            'foo' => 'bar',
        ]);
        $this->assertSame([
            'foo' => 'bar',
        ], Arr::get($button->toArray(), 'additional'));
    }

    /** @test */
    public function it_has_a_button_type()
    {
        $button = Button::create('text');
        $this->assertSame('button', Arr::get($button->toArray(), 'type'));
    }

    /** @test */
    public function it_can_be_json_encoded()
    {
        $button = Button::create('text');

        $this->assertSame(json_encode($button->toArray()), json_encode($button));
    }
}

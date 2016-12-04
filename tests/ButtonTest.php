<?php

namespace Mpociot\BotMan\Tests;

use Mpociot\BotMan\Button;
use Illuminate\Support\Arr;
use PHPUnit_Framework_TestCase;

class ButtonTest extends PHPUnit_Framework_TestCase
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

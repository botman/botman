<?php

namespace Mpociot\BotMan\Tests;

use Illuminate\Support\Arr;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Facebook\ElementButton;
use Mpociot\BotMan\Facebook\ButtonTemplate;

class ButtonTemplateTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $template = new ButtonTemplate('Here are some buttons');
        $this->assertInstanceOf(ButtonTemplate::class, $template);
    }

    /**
     * @test
     **/
    public function it_can_add_a_button()
    {
        $template = new ButtonTemplate('Here are some buttons');
        $template->addButton(ElementButton::create('button1'));

        $this->assertSame('button1', Arr::get($template->toArray(), 'attachment.payload.buttons.0.title'));
    }

    /**
     * @test
     **/
    public function it_can_add_multiple_buttons()
    {
        $template = new ButtonTemplate('Here are some buttons');
        $template->addButtons([ElementButton::create('button1'), ElementButton::create('button2')]);

        $this->assertSame('button1', Arr::get($template->toArray(), 'attachment.payload.buttons.0.title'));
        $this->assertSame('button2', Arr::get($template->toArray(), 'attachment.payload.buttons.1.title'));
    }
}

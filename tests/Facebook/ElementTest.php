<?php

namespace Mpociot\BotMan\Tests;

use Illuminate\Support\Arr;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Facebook\Element;
use Mpociot\BotMan\Facebook\ElementButton;

class ElementTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $button = new Element('BotMan Release');
        $this->assertInstanceOf(Element::class, $button);
    }

    /**
     * @test
     **/
    public function it_can_set_title()
    {
        $element = new Element('BotMan Release');

        $this->assertSame('BotMan Release', Arr::get($element->toArray(), 'title'));
    }

    /**
     * @test
     **/
    public function it_can_set_image_url()
    {
        $element = new Element('BotMan Release');
        $element->image('http://botman.io/img/botman-body.png');

        $this->assertSame('http://botman.io/img/botman-body.png', Arr::get($element->toArray(), 'image_url'));
    }

    /**
     * @test
     **/
    public function it_can_set_item_url()
    {
        $element = new Element('BotMan Release');
        $element->itemUrl('http://botman.io/');

        $this->assertSame('http://botman.io/', Arr::get($element->toArray(), 'item_url'));
    }

    /**
     * @test
     **/
    public function it_can_set_subtitle()
    {
        $element = new Element('BotMan Release');
        $element->subtitle('This is huge');

        $this->assertSame('This is huge', Arr::get($element->toArray(), 'subtitle'));
    }

    /**
     * @test
     **/
    public function it_can_add_a_button()
    {
        $template = new Element('Here are some buttons');
        $template->addButton(ElementButton::create('button1'));

        $this->assertSame('button1', Arr::get($template->toArray(), 'buttons.0.title'));
    }

    /**
     * @test
     **/
    public function it_can_add_multiple_buttons()
    {
        $template = new Element('Here are some buttons');
        $template->addButtons([ElementButton::create('button1'), ElementButton::create('button2')]);

        $this->assertSame('button1', Arr::get($template->toArray(), 'buttons.0.title'));
        $this->assertSame('button2', Arr::get($template->toArray(), 'buttons.1.title'));
    }

    /**
     * @test
     */
    public function it_can_add_a_plain_array_button()
    {
        $template = new Element('Here are some buttons');

        $template->addButton([
            'type' => 'element_share',
        ]);

        $this->assertSame('element_share', Arr::get($template->toArray(), 'buttons.0.type'));
    }

    /**
     * @test
     */
    public function it_can_add_multiple_plain_array_buttons()
    {
        $template = new Element('Here are some buttons');

        $template->addButtons([
            [
                'type' => 'element_share',
            ],
            [
                'type' => 'postback',
                'title' => 'Element 2',
                'payload' => 'ELEMENT_2',
            ],
            ElementButton::create('button3'),
        ]);

        $this->assertSame('element_share', Arr::get($template->toArray(), 'buttons.0.type'));
        $this->assertSame('postback', Arr::get($template->toArray(), 'buttons.1.type'));
        $this->assertSame('button3', Arr::get($template->toArray(), 'buttons.2.title'));
    }
}

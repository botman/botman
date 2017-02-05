<?php

namespace Mpociot\BotMan\Tests;

use Illuminate\Support\Arr;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Facebook\Element;
use Mpociot\BotMan\Facebook\GenericTemplate;

class GenericTemplateTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $template = new GenericTemplate;
        $this->assertInstanceOf(GenericTemplate::class, $template);
    }

    /**
     * @test
     **/
    public function it_can_add_an_element()
    {
        $template = new GenericTemplate;
        $template->addElement(Element::create('BotMan Documentation'));

        $this->assertSame('BotMan Documentation',
            Arr::get($template->toArray(), 'attachment.payload.elements.0.title'));
    }

    /**
     * @test
     **/
    public function it_can_add_multiple_elements()
    {
        $template = new GenericTemplate;
        $template->addElements([Element::create('BotMan Documentation'), Element::create('BotMan Laravel Starter')]);

        $this->assertSame('BotMan Documentation',
            Arr::get($template->toArray(), 'attachment.payload.elements.0.title'));
        $this->assertSame('BotMan Laravel Starter',
            Arr::get($template->toArray(), 'attachment.payload.elements.1.title'));
    }
}

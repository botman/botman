<?php

namespace Mpociot\BotMan\Tests;

use Illuminate\Support\Arr;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Facebook\ElementButton;

class ElementButtonTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $button = new ElementButton('click me');
        $this->assertInstanceOf(ElementButton::class, $button);
    }

    /**
     * @test
     **/
    public function it_can_set_title()
    {
        $button = new ElementButton('click me');

        $this->assertSame('click me', Arr::get($button->toArray(), 'title'));
    }

    /**
     * @test
     **/
    public function standard_type_is_web_url()
    {
        $button = new ElementButton('click me');

        $this->assertSame('web_url', Arr::get($button->toArray(), 'type'));
    }

    /**
     * @test
     **/
    public function it_can_set_type()
    {
        $button = new ElementButton('click me');
        $button->type('postback');

        $this->assertSame('postback', Arr::get($button->toArray(), 'type'));
    }

    /**
     * @test
     **/
    public function it_can_set_url()
    {
        $button = new ElementButton('click me');
        $button->url('http://botman.io/');

        $this->assertSame('http://botman.io/', Arr::get($button->toArray(), 'url'));
    }

    /**
     * @test
     **/
    public function it_can_set_payload()
    {
        $button = new ElementButton('click me');
        $button->payload('clickme')->type('postback');

        $this->assertSame('clickme', Arr::get($button->toArray(), 'payload'));
    }
}

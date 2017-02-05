<?php

namespace Mpociot\BotMan\Tests;

use Illuminate\Support\Arr;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Facebook\ReceiptElement;

class ReceiptElementTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $element = new ReceiptElement('T-Shirt');
        $this->assertInstanceOf(ReceiptElement::class, $element);
    }

    /**
     * @test
     **/
    public function it_can_set_title()
    {
        $element = new ReceiptElement('T-Shirt');

        $this->assertSame('T-Shirt', Arr::get($element->toArray(), 'title'));
    }

    /**
     * @test
     **/
    public function it_can_set_subtitle()
    {
        $element = new ReceiptElement('T-Shirt');
        $element->subtitle('Best shirt ever');

        $this->assertSame('Best shirt ever', Arr::get($element->toArray(), 'subtitle'));
    }

    /**
     * @test
     **/
    public function it_can_set_quantity()
    {
        $element = new ReceiptElement('T-Shirt');
        $element->quantity(30);

        $this->assertSame(30, Arr::get($element->toArray(), 'quantity'));
    }

    /**
     * @test
     **/
    public function it_can_set_price()
    {
        $element = new ReceiptElement('T-Shirt');
        $element->price(19.00);

        $this->assertSame(19.00, Arr::get($element->toArray(), 'price'));
    }

    /**
     * @test
     **/
    public function it_can_set_currency()
    {
        $element = new ReceiptElement('T-Shirt');
        $element->currency('EUR');

        $this->assertSame('EUR', Arr::get($element->toArray(), 'currency'));
    }

    /**
     * @test
     **/
    public function it_can_set_image()
    {
        $element = new ReceiptElement('T-Shirt');
        $element->image('http://botman.io/img/botman-body.png');

        $this->assertSame('http://botman.io/img/botman-body.png', Arr::get($element->toArray(), 'image_url'));
    }
}

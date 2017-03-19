<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\Button;
use Mpociot\BotMan\Facebook\ButtonTemplate;
use Mpociot\BotMan\Facebook\Element;
use Mpociot\BotMan\Facebook\ElementButton;
use Mpociot\BotMan\Facebook\GenericTemplate;
use Mpociot\BotMan\Facebook\ListTemplate;
use Mpociot\BotMan\Facebook\ReceiptAddress;
use Mpociot\BotMan\Facebook\ReceiptAdjustment;
use Mpociot\BotMan\Facebook\ReceiptElement;
use Mpociot\BotMan\Facebook\ReceiptSummary;
use Mpociot\BotMan\Facebook\ReceiptTemplate;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Http\Curl;
use Mpociot\BotMan\Question;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Drivers\ApiDriver;
use Symfony\Component\HttpFoundation\Request;

class ApiDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($responseData, $htmlInterface = null)
    {
        $request = Request::create('', 'POST', $responseData);
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new ApiDriver($request, [], $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('Api', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getDriver([
            'driver' => 'web',
            'message' => 'Hi Julia',
            'userId' => '12345',
        ]);
        $this->assertFalse($driver->matchesRequest());

        $driver = $this->getDriver([
            'driver' => 'api',
            'message' => 'Hi Julia',
            'userId' => '12345',
        ]);
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $driver = $this->getDriver([
            'driver' => 'web',
            'message' => 'Hi Julia',
            'userId' => '12345',
        ]);
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_message_text()
    {
        $driver = $this->getDriver([
            'driver' => 'web',
            'message' => 'Hi Julia',
            'userId' => '12345',
        ]);
        $this->assertSame('Hi Julia', $driver->getMessages()[0]->getMessage());
    }

    /** @test */
    public function it_returns_the_user_id()
    {
        $driver = $this->getDriver([
            'driver' => 'web',
            'message' => 'Hi Julia',
            'userId' => '12345',
        ]);
        $this->assertSame('12345', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_can_reply_string_messages()
    {
        $driver = $this->getDriver([
            'driver' => 'web',
            'message' => 'Hi Julia',
            'userId' => '12345',
        ]);

        $message = new Message('', '', '1234567890');

        $driver->reply('Test one From API', $message);
        $driver->reply('Test two From API', $message);
        $driver->afterMessagesHandled();

        $this->expectOutputString('{"status":200,"messages":[{"type":"text","text":"Test one From API"},{"type":"text","text":"Test two From API"}]}');
    }

    /**
     * @test
     **/
    public function it_replies_to_question_object()
    {
        $driver = $this->getDriver([
            'driver' => 'web',
            'message' => 'Hi Julia',
            'userId' => '12345',
        ]);

        $message = new Message('', '', '1234567890');
        $question = Question::create('What do want to do?')
            ->addButton(Button::create('Stay')->image('https://test.com/image.png')->value('stay'))
            ->addButton(Button::create('Leave'));
        $driver->reply($question, $message);
        $driver->afterMessagesHandled();

        $this->expectOutputString('{"status":200,"messages":[{"type":"buttons","text":"What do want to do?","buttons":[{"type":"postback","text":"Stay","value":"stay"},{"type":"postback","text":"Leave","value":null}]}]}');
    }

    /**
     * @test
     **/
    public function it_replies_to_facebook_button_template()
    {
        $driver = $this->getDriver([
            'driver' => 'web',
            'message' => 'Hi Julia',
            'userId' => '12345',
        ]);

        $message = new Message('', '', '1234567890');
        $template = ButtonTemplate::create('How do you like BotMan so far?')
            ->addButton(ElementButton::create('Quite good')->type('postback')->payload('good'))
            ->addButton(ElementButton::create('Love it!')->url('https://test.at'));

        $driver->reply($template, $message);
        $driver->afterMessagesHandled();

        $this->expectOutputString('{"status":200,"messages":[{"type":"buttons","text":"How do you like BotMan so far?","buttons":[{"type":"postback","text":"Quite good","value":"good"},{"type":"web_url","text":"Love it!","webUrl":"https:\/\/test.at"}]}]}');
    }

    /**
     * @test
     **/
    public function it_replies_to_facebook_list_template()
    {
        $driver = $this->getDriver([
            'driver' => 'web',
            'message' => 'Hi Julia',
            'userId' => '12345',
        ]);

        $message = new Message('', '', '1234567890');
        $template = ListTemplate::create()
            ->useCompactView()
            ->addGlobalButton(
                ElementButton::create('view more')
                    ->url('http://test.at'))
            ->addElement(
                Element::create('BotMan Documentation')
                    ->subtitle('All about BotMan')
                    ->image('http://botman.io/img/botman-body.png')
                    ->addButton(ElementButton::create('tell me more')->payload('tellmemore')->type('postback')))
            ->addElement(
                Element::create('BotMan Laravel Starter')
                    ->image('http://botman.io/img/botman-body.png')
                    ->addButton(ElementButton::create('visit')->url('https://github.com/mpociot/botman-laravel-starter'))
            );

        $driver->reply($template, $message);
        $driver->afterMessagesHandled();

        $this->expectOutputString('{"status":200,"messages":[{"type":"list","elements":[{"title":"BotMan Documentation","subtitle":"All about BotMan","imageUrl":"http:\/\/botman.io\/img\/botman-body.png","buttons":[{"type":"postback","text":"tell me more","value":"tellmemore"}]},{"title":"BotMan Laravel Starter","subtitle":null,"imageUrl":"http:\/\/botman.io\/img\/botman-body.png","buttons":[{"type":"web_url","text":"visit","webUrl":"https:\/\/github.com\/mpociot\/botman-laravel-starter"}]}],"globalButtons":[{"type":"web_url","text":"view more","webUrl":"http:\/\/test.at"}]}]}');
    }

    /**
     * @test
     **/
    public function it_replies_to_facebook_generic_template()
    {
        $driver = $this->getDriver([
            'driver' => 'web',
            'message' => 'Hi Julia',
            'userId' => '12345',
        ]);

        $message = new Message('', '', '1234567890');
        $template = GenericTemplate::create()
            ->addElements([
                Element::create('BotMan Documentation')
                    ->itemUrl('http://botman.io/')
                    ->image('http://screenshots.nomoreencore.com/botman2.png')
                    ->subtitle('All about BotMan')
                    ->addButton(ElementButton::create('visit')->url('http://botman1.io'))
                    ->addButton(ElementButton::create('tell me more')->payload('tellmemore')->type('postback')),
                Element::create('BotMan Laravel Starter')
                    ->itemUrl('https://github.com/mpociot/botman-laravel-starter')
                    ->image('http://screenshots.nomoreencore.com/botman.png')
                    ->subtitle('This is the best way to start with Laravel and BotMan')
                    ->addButton(ElementButton::create('visit')->url('https://github.com/mpociot/botman-laravel-starter')),
        ]);

        $driver->reply($template, $message);
        $driver->afterMessagesHandled();

        $this->expectOutputString('{"status":200,"messages":[{"type":"list","elements":[{"title":"BotMan Documentation","subtitle":"All about BotMan","imageUrl":"http:\/\/screenshots.nomoreencore.com\/botman2.png","itemUrl":"http:\/\/botman.io\/","buttons":[{"type":"web_url","text":"visit","webUrl":"http:\/\/botman1.io"},{"type":"postback","text":"tell me more","value":"tellmemore"}]},{"title":"BotMan Laravel Starter","subtitle":"This is the best way to start with Laravel and BotMan","imageUrl":"http:\/\/screenshots.nomoreencore.com\/botman.png","itemUrl":"https:\/\/github.com\/mpociot\/botman-laravel-starter","buttons":[{"type":"web_url","text":"visit","webUrl":"https:\/\/github.com\/mpociot\/botman-laravel-starter"}]}]}]}');
    }

    /**
    * @test
    **/
    public function it_replies_to_facebook_receipt_template()
    {
        $driver = $this->getDriver([
            'driver' => 'web',
            'message' => 'Hi Julia',
            'userId' => '12345',
        ]);

        $message = new Message('', '', '1234567890');
        $template = ReceiptTemplate::create()
            ->recipientName('Christoph Rumpel')
            ->merchantName('BotMan GmbH')
            ->orderNumber('342343434343')
            ->timestamp('1428444852')
            ->orderUrl('http://test.at')
            ->currency('USD')
            ->paymentMethod('VISA')
            ->addElement(ReceiptElement::create('T-Shirt Small')->price(15.99)->image('http://botman.io/img/botman-body.png')->quantity(2)->subtitle('v1')->currency('USD'))
            ->addElement(ReceiptElement::create('Sticker')->price(2.99)->image('http://botman.io/img/botman-body.png')->subtitle('Logo 1')->currency('USD'))
            ->addAddress(ReceiptAddress::create()->street1('Watsonstreet 12')->city('Bot City')->postalCode(100000)->state('Washington AI')->country('Botmanland'))
            ->addSummary(ReceiptSummary::create()->subtotal(18.98)->shippingCost(10)->totalTax(15)->totalCost(23.98))
            ->addAdjustment(ReceiptAdjustment::create('Laravel Bonus')->amount(5));

        $driver->reply($template, $message);
        $driver->afterMessagesHandled();

        $this->expectOutputString('{"status":200,"messages":[{"type":"receipt","recipient_name":"Christoph Rumpel","merchant_name":"BotMan GmbH","order_number":"342343434343","currency":"USD","payment_method":"VISA","order_url":"http:\/\/test.at","timestamp":"1428444852","elements":[{"title":"T-Shirt Small","subtitle":"v1","imageUrl":"http:\/\/botman.io\/img\/botman-body.png","quantity":2,"price":15.99,"currency":"USD"},{"title":"Sticker","subtitle":"Logo 1","imageUrl":"http:\/\/botman.io\/img\/botman-body.png","quantity":null,"price":2.99,"currency":"USD"}],"address":{"street_1":"Watsonstreet 12","street_2":null,"city":"Bot City","postal_code":100000,"state":"Washington AI","country":"Botmanland"},"summary":{"subtotal":18.98,"shipping_cost":10,"total_tax":15,"total_cost":23.98},"adjustments":[{"name":"Laravel Bonus","amount":5}]}]}');
    }
}

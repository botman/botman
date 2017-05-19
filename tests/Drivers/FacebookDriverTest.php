<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\Button;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\BotManFactory;
use Mpociot\BotMan\Attachments\File;
use Mpociot\BotMan\Cache\ArrayCache;
use Mpociot\BotMan\Attachments\Audio;
use Mpociot\BotMan\Attachments\Image;
use Mpociot\BotMan\DriverEvents\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Mpociot\BotMan\Drivers\Facebook\FacebookDriver;
use Mpociot\BotMan\DriverEvents\Facebook\MessagingReads;
use Mpociot\BotMan\DriverEvents\Facebook\MessagingOptins;
use Mpociot\BotMan\DriverEvents\Facebook\MessagingPostbacks;
use Mpociot\BotMan\DriverEvents\Facebook\MessagingReferrals;
use Mpociot\BotMan\DriverEvents\Facebook\MessagingDeliveries;
use Mpociot\BotMan\DriverEvents\Facebook\MessagingCheckoutUpdates;

class FacebookDriverTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    private function getRequest($responseData)
    {
        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($responseData);

        return $request;
    }

    private function getDriver($responseData, array $config = ['facebook_token' => 'Foo'], $signature = '')
    {
        $request = $this->getRequest($responseData);
        $request->headers->set('X_HUB_SIGNATURE', $signature);

        return new FacebookDriver($request, $config, new Curl());
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver('');
        $this->assertSame('Facebook', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $request = '{}';
        $driver = $this->getDriver($request);
        $this->assertFalse($driver->matchesRequest());

        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"message":{"mid":"mid.1480279487147:4388d3b344","seq":36,"text":"Hi"}}]}]}';
        $driver = $this->getDriver($request);
        $this->assertTrue($driver->matchesRequest());

        $config = ['facebook_token' => 'Foo', 'facebook_app_secret' => 'Bar'];
        $request = '{}';
        $driver = $this->getDriver($request, $config);
        $this->assertFalse($driver->matchesRequest());

        $signature = 'Foo';
        $config = ['facebook_token' => 'Foo', 'facebook_app_secret' => 'Bar'];
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"message":{"mid":"mid.1480279487147:4388d3b344","seq":36,"text":"Hi"}}]}]}';
        $driver = $this->getDriver($request, $config, $signature);
        $this->assertFalse($driver->matchesRequest());

        $signature = 'sha1=74432bfe572675092cc81b5ac903ff3f971b04e5';
        $config = ['facebook_token' => 'Foo', 'facebook_app_secret' => 'Bar'];
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"message":{"mid":"mid.1480279487147:4388d3b344","seq":36,"text":"Hi"}}]}]}';
        $driver = $this->getDriver($request, $config, $signature);
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_matches_the_request_using_the_driver_manager()
    {
        $request = $this->getRequest('{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"message":{"mid":"mid.1480279487147:4388d3b344","seq":36,"text":"Hi"}}]}]}');

        $botman = BotManFactory::create([], new ArrayCache(), $request);
        $this->assertInstanceOf(FacebookDriver::class, $botman->getDriver());
    }

    /** @test */
    public function it_can_originate_messages()
    {
        $botman = BotManFactory::create([], new ArrayCache());

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890',
                ],
                'message' => [
                    'text' => 'Test',
                ],
                'access_token' => 'Foo',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');

        $driver = new FacebookDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $user_id = '1234567890';
        $botman->say('Test', $user_id, $driver);

        $this->assertInstanceOf(FacebookDriver::class, $botman->getDriver());
    }

    /** @test */
    public function it_returns_the_message()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"message":{"mid":"mid.1480279487147:4388d3b344","seq":36,"text":"Hi Julia"}}]}]}';
        $driver = $this->getDriver($request);

        $this->assertSame('Hi Julia', $driver->getMessages()[0]->getText());

        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{}]}]}';
        $driver = $this->getDriver($request);

        $this->assertSame('', $driver->getMessages()[0]->getText());
    }

    /** @test */
    public function it_returns_the_user_object()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"message":{"mid":"mid.1480279487147:4388d3b344","seq":36,"text":"Hi Julia"}}]}]}';
        $driver = $this->getDriver($request);

        $message = $driver->getMessages()[0];
        $user = $driver->getUser($message);

        $this->assertSame($user->getId(), '1433960459967306');
        $this->assertNull($user->getFirstName());
        $this->assertNull($user->getLastName());
        $this->assertNull($user->getUsername());
    }

    /** @test */
    public function it_returns_an_empty_message_if_nothing_matches()
    {
        $request = '';
        $driver = $this->getDriver($request);

        $this->assertSame('', $driver->getMessages()[0]->getText());
    }

    /** @test */
    public function it_detects_bots()
    {
        $driver = $this->getDriver('');
        $this->assertFalse($driver->isBot());
    }

    /** @test */
    public function it_returns_the_user_id()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"message":{"mid":"mid.1480279487147:4388d3b344","seq":36,"text":"Hi Julia"}}]}]}';
        $driver = $this->getDriver($request);

        $this->assertSame('1433960459967306', $driver->getMessages()[0]->getSender());
    }

    /** @test */
    public function it_returns_the_recipient_id()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"message":{"mid":"mid.1480279487147:4388d3b344","seq":36,"text":"Hi Julia"}}]}]}';
        $driver = $this->getDriver($request);

        $this->assertSame('111899832631525', $driver->getMessages()[0]->getRecipient());
    }

    /** @test */
    public function it_can_reply_string_messages()
    {
        $responseData = [
            'object' => 'page',
            'event' => [
                [
                    'messaging' => [
                        [
                            'sender' => [
                                'id' => '1234567890',
                            ],
                            'recipient' => [
                                'id' => '0987654321',
                            ],
                            'message' => [
                                'text' => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890',
                ],
                'message' => [
                    'text' => 'Test',
                ],
                'access_token' => 'Foo',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new FacebookDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $message = new Message('', '1234567890', '');
        $driver->sendPayload($driver->buildServicePayload('Test', $message));
    }

    /** @test */
    public function it_can_reply_with_additional_parameters()
    {
        $responseData = [
            'object' => 'page',
            'event' => [
                [
                    'messaging' => [
                        [
                            'sender' => [
                                'id' => '1234567890',
                            ],
                            'recipient' => [
                                'id' => '0987654321',
                            ],
                            'message' => [
                                'text' => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890',
                ],
                'message' => [
                    'text' => 'Test',
                ],
                'access_token' => 'Foo',
                'custom' => 'payload',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new FacebookDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $message = new Message('', '1234567890', '');
        $driver->sendPayload($driver->buildServicePayload('Test', $message, [
            'custom' => 'payload',
        ]));
    }

    /** @test */
    public function it_returns_answer_from_interactive_messages()
    {
        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode([]));

        $driver = new FacebookDriver($request, [
            'facebook_token' => 'Foo',
        ], m::mock(Curl::class));

        $message = new Message('Red', '0987654321', '1234567890', [
            'sender' => [
                'id' => '1234567890',
            ],
            'recipient' => [
                'id' => '0987654321',
            ],
            'message' => [
                'text' => 'Red',
                'quick_reply' => [
                    'payload' => 'DEVELOPER_DEFINED_PAYLOAD',
                ],
            ],
        ]);

        $this->assertSame('Red', $driver->getConversationAnswer($message)->getText());
        $this->assertSame($message, $driver->getConversationAnswer($message)->getMessage());
        $this->assertSame('DEVELOPER_DEFINED_PAYLOAD', $driver->getConversationAnswer($message)->getValue());
    }

    /** @test */
    public function it_returns_answer_from_regular_messages()
    {
        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode([]));

        $driver = new FacebookDriver($request, [
            'facebook_token' => 'Foo',
        ], m::mock(Curl::class));

        $message = new Message('Red', '0987654321', '1234567890', [
            'sender' => [
                'id' => '1234567890',
            ],
            'recipient' => [
                'id' => '0987654321',
            ],
            'message' => [
                'text' => 'Red',
            ],
        ]);

        $this->assertSame('Red', $driver->getConversationAnswer($message)->getText());
        $this->assertSame(null, $driver->getConversationAnswer($message)->getValue());
    }

    /** @test */
    public function it_can_reply_questions()
    {
        $question = Question::create('How are you doing?')
            ->addButton(Button::create('Great')->value('great'))
            ->addButton(Button::create('Good')->value('good'));

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890',
                ],
                'message' => [
                    'text' => 'How are you doing?',
                    'quick_replies' => [
                        [
                            'content_type' => 'text',
                            'title' => 'Great',
                            'payload' => 'great',
                            'image_url' => null,
                        ],
                        [
                            'content_type' => 'text',
                            'title' => 'Good',
                            'payload' => 'good',
                            'image_url' => null,
                        ],
                    ],
                ],
                'access_token' => 'Foo',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('[]');

        $driver = new FacebookDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $message = new Message('', '1234567890', '');
        $driver->sendPayload($driver->buildServicePayload($question, $message));
    }

    /** @test */
    public function it_can_reply_questions_with_additional_button_parameters()
    {
        $question = Question::create('How are you doing?')
            ->addButton(Button::create('Great')->value('great')->additionalParameters(['foo' => 'bar']))
            ->addButton(Button::create('Good')->value('good'));

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890',
                ],
                'message' => [
                    'text' => 'How are you doing?',
                    'quick_replies' => [
                        [
                            'content_type' => 'text',
                            'title' => 'Great',
                            'payload' => 'great',
                            'image_url' => null,
                            'foo' => 'bar',
                        ],
                        [
                            'content_type' => 'text',
                            'title' => 'Good',
                            'payload' => 'good',
                            'image_url' => null,
                        ],
                    ],
                ],
                'access_token' => 'Foo',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('[]');

        $driver = new FacebookDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $message = new Message('', '1234567890', '');
        $driver->sendPayload($driver->buildServicePayload($question, $message));
    }

    /** @test */
    public function it_is_configured()
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new FacebookDriver($request, [
            'facebook_token' => 'token',
        ], $htmlInterface);

        $this->assertTrue($driver->isConfigured());

        $driver = new FacebookDriver($request, [
            'facebook_token' => null,
        ], $htmlInterface);

        $this->assertFalse($driver->isConfigured());

        $driver = new FacebookDriver($request, [], $htmlInterface);

        $this->assertFalse($driver->isConfigured());
    }

    /** @test */
    public function it_can_reply_message_objects()
    {
        $responseData = [
            'object' => 'page',
            'event' => [
                [
                    'messaging' => [
                        [
                            'sender' => [
                                'id' => '1234567890',
                            ],
                            'recipient' => [
                                'id' => '0987654321',
                            ],
                            'message' => [
                                'text' => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890',
                ],
                'message' => [
                    'text' => 'Test',
                ],
                'access_token' => 'Foo',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new FacebookDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $message = new Message('', '1234567890', '');
        $driver->sendPayload($driver->buildServicePayload(\Mpociot\BotMan\Messages\Message::create('Test'), $message));
    }

    /** @test */
    public function it_can_reply_message_objects_with_image()
    {
        $responseData = [
            'object' => 'page',
            'event' => [
                [
                    'messaging' => [
                        [
                            'sender' => [
                                'id' => '1234567890',
                            ],
                            'recipient' => [
                                'id' => '0987654321',
                            ],
                            'message' => [
                                'text' => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890',
                ],
                'message' => [
                    'attachment' => [
                        'type' => 'image',
                        'payload' => [
                            'url' => 'http://image.url//foo.png',
                        ],
                    ],
                ],
                'access_token' => 'Foo',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new FacebookDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $message = new Message('', '1234567890', '');
        $driver->sendPayload($driver->buildServicePayload(\Mpociot\BotMan\Messages\Message::create('Test', Image::url('http://image.url//foo.png')), $message));
    }

    /** @test */
    public function it_can_reply_message_objects_with_audio()
    {
        $responseData = [
            'object' => 'page',
            'event' => [
                [
                    'messaging' => [
                        [
                            'sender' => [
                                'id' => '1234567890',
                            ],
                            'recipient' => [
                                'id' => '0987654321',
                            ],
                            'message' => [
                                'text' => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890',
                ],
                'message' => [
                    'attachment' => [
                        'type' => 'audio',
                        'payload' => [
                            'url' => 'http://image.url//foo.mp3',
                        ],
                    ],
                ],
                'access_token' => 'Foo',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new FacebookDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $message = new Message('', '1234567890', '');
        $driver->sendPayload($driver->buildServicePayload(\Mpociot\BotMan\Messages\Message::create('Test', Audio::url('http://image.url//foo.mp3')), $message));
    }

    /** @test */
    public function it_can_reply_message_objects_with_file()
    {
        $responseData = [
            'object' => 'page',
            'event' => [
                [
                    'messaging' => [
                        [
                            'sender' => [
                                'id' => '1234567890',
                            ],
                            'recipient' => [
                                'id' => '0987654321',
                            ],
                            'message' => [
                                'text' => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890',
                ],
                'message' => [
                    'attachment' => [
                        'type' => 'file',
                        'payload' => [
                            'url' => 'http://image.url//foo.pdf',
                        ],
                    ],
                ],
                'access_token' => 'Foo',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new FacebookDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $message = new Message('', '1234567890', '');
        $driver->sendPayload($driver->buildServicePayload(\Mpociot\BotMan\Messages\Message::create('Test', File::url('http://image.url//foo.pdf')), $message));
    }

    /** @test */
    public function it_calls_postback_event()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"postback":{"payload":"MY_PAYLOAD"}}]}]}';
        $driver = $this->getDriver($request);

        $event = $driver->hasMatchingEvent();
        $this->assertInstanceOf(MessagingPostbacks::class, $event);
        $this->assertSame('messaging_postbacks', $event->getName());
    }

    /** @test */
    public function it_calls_referral_event()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"referral":{"ref":"MY_REF","source": "MY_SOURCE","type": "MY_TYPE"}}]}]}';
        $driver = $this->getDriver($request);

        $event = $driver->hasMatchingEvent();
        $this->assertInstanceOf(MessagingReferrals::class, $event);
        $this->assertSame('messaging_referrals', $event->getName());
    }

    /** @test */
    public function it_calls_optin_event()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"optin": {"ref":"optin","user_ref":"1234"}}]}]}';
        $driver = $this->getDriver($request);

        $event = $driver->hasMatchingEvent();
        $this->assertInstanceOf(MessagingOptins::class, $event);
        $this->assertSame('messaging_optins', $event->getName());
    }

    /** @test */
    public function it_calls_delivery_event()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"USER_ID"},"recipient":{"id":"PAGE_ID"},"delivery":{"mids":["mid.1458668856218:ed81099e15d3f4f233"],"watermark":1458668856253,"seq":37}}]}]}';
        $driver = $this->getDriver($request);

        $event = $driver->hasMatchingEvent();
        $this->assertInstanceOf(MessagingDeliveries::class, $event);
        $this->assertSame('messaging_deliveries', $event->getName());
    }

    /** @test */
    public function it_calls_read_event()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"USER_ID"},"recipient":{"id":"PAGE_ID"},"timestamp":1458668856463,"read":{"watermark":1458668856253,"seq":38}}]}]}';
        $driver = $this->getDriver($request);

        $event = $driver->hasMatchingEvent();
        $this->assertInstanceOf(MessagingReads::class, $event);
        $this->assertSame('messaging_reads', $event->getName());
    }

    /** @test */
    public function it_calls_checkout_update_event()
    {
        $request = '{"object": "page","entry": [{"id": "PAGE_ID","time": 1473204787206,"messaging": [{"recipient": {"id": "PAGE_ID"},"timestamp": 1473204787206,"sender": {"id": "USER_ID"},"checkout_update": {"payload": "DEVELOPER_DEFINED_PAYLOAD","shipping_address": {"id": 10105655000959552,"country": "US","city": "MENLO PARK","street1": "1 Hacker Way","street2": "","state": "CA","postal_code": "94025"}}}]}]}';
        $driver = $this->getDriver($request);

        $event = $driver->hasMatchingEvent();
        $this->assertInstanceOf(MessagingCheckoutUpdates::class, $event);
        $this->assertSame('messaging_checkout_updates', $event->getName());
    }

    /** @test */
    public function it_calls_generic_event_for_unkown_facebook_events()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"USER_ID"},"recipient":{"id":"PAGE_ID"},"timestamp":1458668856463,"foo":{"watermark":1458668856253,"seq":38}}]}]}';
        $driver = $this->getDriver($request);

        $event = $driver->hasMatchingEvent();
        $this->assertInstanceOf(GenericEvent::class, $event);
        $this->assertSame('foo', $event->getName());
    }
}

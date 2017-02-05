<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\Button;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Drivers\TelegramDriver;
use Symfony\Component\HttpFoundation\Request;

class TelegramDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($responseData, $htmlInterface = null)
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new TelegramDriver($request, [], $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('Telegram', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getDriver([
            'event' => [
                'text' => 'bar',
            ],
        ]);
        $this->assertFalse($driver->matchesRequest());

        $driver = $this->getDriver([
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => 'chat_id',
                ],
                'date' => '1480369277',
                'text' => 'Telegram Text',
            ],
        ]);
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $driver = $this->getDriver([
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => 'chat_id',
                ],
                'date' => '1480369277',
                'text' => 'Telegram Text',
            ],
        ]);
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_message_text()
    {
        $driver = $this->getDriver([
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => 'chat_id',
                ],
                'date' => '1480369277',
                'text' => 'Hi Julia',
            ],
        ]);
        $this->assertSame('Hi Julia', $driver->getMessages()[0]->getMessage());
    }

    /** @test */
    public function it_detects_bots()
    {
        $driver = $this->getDriver([
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => 'chat_id',
                ],
                'date' => '1480369277',
                'text' => 'Telegram Text',
            ],
        ]);
        $this->assertFalse($driver->isBot());
    }

    /** @test */
    public function it_returns_the_user_id()
    {
        $driver = $this->getDriver([
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => 'chat_id',
                ],
                'date' => '1480369277',
                'text' => 'Telegram Text',
            ],
            'entities' => [],
        ]);
        $this->assertSame('from_id', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_returns_the_channel_id()
    {
        $driver = $this->getDriver([
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => 'chat_id',
                ],
                'date' => '1480369277',
                'text' => 'Telegram Text',
            ],
            'entities' => [],
        ]);
        $this->assertSame('chat_id', $driver->getMessages()[0]->getChannel());
    }

    /** @test */
    public function it_detects_users_from_interactive_messages()
    {
        $driver = $this->getDriver([
            'update_id' => '1234567890',
            'callback_query' => [
                'id' => '11717237123',
                'from' => [
                    'id' => 'from_id',
                ],
                'message' => [
                    'message_id' => '123',
                    'from' => [
                        'id' => 'from_id',
                    ],
                    'chat' => [
                        'id' => 'chat_id',
                    ],
                    'date' => '1480369277',
                    'text' => 'Telegram Text',
                ],
            ],
            'data' => 'FooBar',
        ]);

        $this->assertSame('from_id', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_detects_channels_from_interactive_messages()
    {
        $driver = $this->getDriver([
            'update_id' => '1234567890',
            'callback_query' => [
                'id' => '11717237123',
                'from' => [
                    'id' => 'from_id',
                ],
                'message' => [
                    'message_id' => '123',
                    'from' => [
                        'id' => 'from_id',
                    ],
                    'chat' => [
                        'id' => 'chat_id',
                    ],
                    'date' => '1480369277',
                    'text' => 'Telegram Text',
                ],
            ],
            'data' => 'FooBar',
        ]);

        $this->assertSame('chat_id', $driver->getMessages()[0]->getChannel());
    }

    /** @test */
    public function it_returns_payload_from_interactive_messages()
    {
        $payload = [
            'message_id' => '123',
            'from' => [
                'id' => 'from_id',
            ],
            'chat' => [
                'id' => 'chat_id',
            ],
            'date' => '1480369277',
            'text' => 'Telegram Text',
        ];

        $driver = $this->getDriver([
            'update_id' => '1234567890',
            'callback_query' => [
                'id' => '11717237123',
                'from' => [
                    'id' => 'from_id',
                ],
                'message' => $payload,
            ],
            'data' => 'FooBar',
        ]);

        $this->assertSame($payload, $driver->getMessages()[0]->getPayload());
    }

    /** @test */
    public function it_returns_answer_from_interactive_messages_and_edits_original_message()
    {
        $responseData = [
            'update_id' => '1234567890',
            'callback_query' => [
                'id' => '11717237123',
                'from' => [
                    'id' => 'from_id',
                ],
                'message' => [
                    'message_id' => '123',
                    'from' => [
                        'id' => 'from_id',
                    ],
                    'chat' => [
                        'id' => 'chat_id',
                    ],
                    'date' => '1480369277',
                    'text' => 'Telegram Text',
                ],
                'data' => 'FooBar',
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->twice()
            ->with('https://api.telegram.org/botTELEGRAM-BOT-TOKEN/editMessageReplyMarkup', [], [
                'chat_id' => 'chat_id',
                'message_id' => '123',
                'inline_keyboard' => [],
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new TelegramDriver($request, [
            'telegram_token' => 'TELEGRAM-BOT-TOKEN',
        ], $html);

        $message = $driver->getMessages()[0];
        $this->assertSame('FooBar', $driver->getConversationAnswer($message)->getText());
        $this->assertSame($message, $driver->getConversationAnswer($message)->getMessage());
    }

    /** @test */
    public function it_can_reply_string_messages()
    {
        $responseData = [
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => '12345',
                ],
                'date' => '1480369277',
                'text' => 'Telegram Text',
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://api.telegram.org/botTELEGRAM-BOT-TOKEN/sendMessage', [], [
                'chat_id' => '12345',
                'text' => 'Test',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new TelegramDriver($request, [
            'telegram_token' => 'TELEGRAM-BOT-TOKEN',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply('Test', $message);
    }

    /** @test */
    public function it_can_reply_questions()
    {
        $responseData = [
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => '12345',
                ],
                'date' => '1480369277',
                'text' => 'Telegram Text',
            ],
        ];

        $question = Question::create('How are you doing?')
            ->addButton(Button::create('Great')->value('great'))
            ->addButton(Button::create('Good')->value('good'));

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://api.telegram.org/botTELEGRAM-BOT-TOKEN/sendMessage', [], [
                'chat_id' => '12345',
                'text' => 'How are you doing?',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'Great',
                                'callback_data' => 'great',
                            ],
                        ],
                        [
                            [
                                'text' => 'Good',
                                'callback_data' => 'good',
                            ],
                        ],
                    ],
                ], true),
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new TelegramDriver($request, [
            'telegram_token' => 'TELEGRAM-BOT-TOKEN',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply($question, $message);
    }

    /** @test */
    public function it_can_reply_with_additional_parameters()
    {
        $responseData = [
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => '12345',
                ],
                'date' => '1480369277',
                'text' => 'Telegram Text',
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://api.telegram.org/botTELEGRAM-BOT-TOKEN/sendMessage', [], [
                'chat_id' => '12345',
                'text' => 'Test',
                'foo' => 'bar',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new TelegramDriver($request, [
            'telegram_token' => 'TELEGRAM-BOT-TOKEN',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply('Test', $message, [
            'foo' => 'bar',
        ]);
    }

    /** @test */
    public function it_is_configured()
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new TelegramDriver($request, [
            'telegram_token' => 'TELEGRAM-BOT-TOKEN',
        ], $htmlInterface);

        $this->assertTrue($driver->isConfigured());

        $driver = new TelegramDriver($request, [
            'telegram_token' => null,
        ], $htmlInterface);

        $this->assertFalse($driver->isConfigured());

        $driver = new TelegramDriver($request, [], $htmlInterface);

        $this->assertFalse($driver->isConfigured());
    }

    /** @test */
    public function it_can_reply_message_objects()
    {
        $responseData = [
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => '12345',
                ],
                'date' => '1480369277',
                'text' => 'Telegram Text',
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://api.telegram.org/botTELEGRAM-BOT-TOKEN/sendMessage', [], [
                'chat_id' => '12345',
                'text' => 'Test',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new TelegramDriver($request, [
            'telegram_token' => 'TELEGRAM-BOT-TOKEN',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply(\Mpociot\BotMan\Messages\Message::create('Test'), $message);
    }

    /** @test */
    public function it_can_reply_message_objects_with_image()
    {
        $responseData = [
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => '12345',
                ],
                'date' => '1480369277',
                'text' => 'Telegram Text',
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://api.telegram.org/botTELEGRAM-BOT-TOKEN/sendPhoto', [], [
                'chat_id' => '12345',
                'photo' => 'http://image.url/foo.png',
                'caption' => 'Test',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new TelegramDriver($request, [
            'telegram_token' => 'TELEGRAM-BOT-TOKEN',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply(\Mpociot\BotMan\Messages\Message::create('Test', 'http://image.url/foo.png'), $message);
    }

    /** @test */
    public function it_can_reply_message_objects_with_gif_image()
    {
        $responseData = [
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => '12345',
                ],
                'date' => '1480369277',
                'text' => 'Telegram Text',
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://api.telegram.org/botTELEGRAM-BOT-TOKEN/sendDocument', [], [
                'chat_id' => '12345',
                'document' => 'http://image.url/foo.gif',
                'caption' => 'Test',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new TelegramDriver($request, [
            'telegram_token' => 'TELEGRAM-BOT-TOKEN',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply(\Mpociot\BotMan\Messages\Message::create('Test', 'http://image.url/foo.gif'), $message);
    }

    /** @test */
    public function it_can_reply_message_objects_with_video()
    {
        $responseData = [
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => '12345',
                ],
                'date' => '1480369277',
                'text' => 'Telegram Text',
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://api.telegram.org/botTELEGRAM-BOT-TOKEN/sendVideo', [], [
                'chat_id' => '12345',
                'video' => 'http://image.url/foo.mp4',
                'caption' => 'Test',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new TelegramDriver($request, [
            'telegram_token' => 'TELEGRAM-BOT-TOKEN',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply(\Mpociot\BotMan\Messages\Message::create('Test')->video('http://image.url/foo.mp4'), $message);
    }
}

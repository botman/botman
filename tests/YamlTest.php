<?php

namespace BotMan\BotMan\Tests;

use BotMan\BotMan\Yaml\Parser;
use PHPUnit\Framework\TestCase;

class YamlTest extends TestCase
{
    /** @test */
    public function it_can_parse_yaml_with_types()
    {
        $yaml = new Parser(file_get_contents(__DIR__.'/Fixtures/TestContent.yml'));
        $messages = $yaml->getMessagesForInstruction('gettingStarted');

        $this->assertCount(4, $messages);
        $this->assertSame('reply', $messages[0]['method']);
        $this->assertSame('Hello!', $messages[0]['arguments'][0]);

        $this->assertSame('typesAndWaits', $messages[1]['method']);
        $this->assertSame(2, $messages[1]['arguments'][0]);

        $this->assertSame('reply', $messages[2]['method']);
        $this->assertSame('How are you?', $messages[2]['arguments'][0]);

        $this->assertSame('typesAndWaits', $messages[3]['method']);
        $this->assertSame(1, $messages[3]['arguments'][0]);
    }

    /** @test */
    public function it_can_parse_flat_yaml()
    {
        $yaml = new Parser(file_get_contents(__DIR__.'/Fixtures/TestContent.yml'));
        $messages = $yaml->getMessagesForInstruction('simple');

        $this->assertCount(2, $messages);
        $this->assertSame('reply', $messages[0]['method']);
        $this->assertSame('Hello!', $messages[0]['arguments'][0]);

        $this->assertSame('reply', $messages[1]['method']);
        $this->assertSame('How are you?', $messages[1]['arguments'][0]);
    }

    /** @test */
    public function it_can_parse_random_responses_yaml()
    {
        $yaml = new Parser(file_get_contents(__DIR__.'/Fixtures/TestContent.yml'));
        $messages = $yaml->getMessagesForInstruction('random');

        $this->assertCount(2, $messages);
        $this->assertSame('randomReply', $messages[0]['method']);
        $this->assertSame(['Hello!', 'Morning!'], $messages[0]['arguments'][0]);

        $this->assertSame('reply', $messages[1]['method']);
        $this->assertSame('How are you?', $messages[1]['arguments'][0]);
    }

    /** @test */
    public function it_can_parse_mustache_templates()
    {
        $yaml = new Parser(file_get_contents(__DIR__.'/Fixtures/TestContent.yml'));
        $data = [
            'example' => 'foobar',
        ];

        $messages = $yaml->getMessagesForInstruction('mustache', $data);

        $this->assertCount(2, $messages);
        $this->assertSame('reply', $messages[0]['method']);
        $this->assertSame('Hi foobar', $messages[0]['arguments'][0]);

        $this->assertSame('reply', $messages[1]['method']);
        $this->assertSame('How are you?', $messages[1]['arguments'][0]);
    }

    /** @test */
    public function it_can_parse_mustache_templates_with_blocks()
    {
        $yaml = new Parser(file_get_contents(__DIR__.'/Fixtures/TestContent.yml'));
        $data = [
            'tweets' => [
                [
                    'author' => 'Foo',
                    'tweet' => 'Tweet #1',
                ],
                [
                    'author' => 'Bar',
                    'tweet' => 'Tweet #2',
                ],
            ],
        ];
        $messages = $yaml->getMessagesForInstruction('sendTweets', $data);

        $this->assertCount(3, $messages);
        $this->assertSame('reply', $messages[0]['method']);
        $this->assertSame('Here\'s the latest tweets', $messages[0]['arguments'][0]);

        $this->assertSame('reply', $messages[1]['method']);
        $this->assertSame('Sent by: Foo'.PHP_EOL.'Tweet: Tweet #1'.PHP_EOL, $messages[1]['arguments'][0]);

        $this->assertSame('reply', $messages[2]['method']);
        $this->assertSame('Sent by: Bar'.PHP_EOL.'Tweet: Tweet #2'.PHP_EOL, $messages[2]['arguments'][0]);
    }
}

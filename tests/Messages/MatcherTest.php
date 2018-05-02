<?php

namespace BotMan\BotMan\tests\Messages;

use PHPUnit\Framework\TestCase;
use BotMan\BotMan\Messages\Matcher;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

class MatcherTest extends TestCase
{
    /**
     * Dataprovider:
     *  [[$input, $pattern, $expected]].
     *
     * @return array
     */
    public function incoming_message_provider()
    {
        $messages = [
            ['foo', 'foo', true],
            ['foo ', 'foo', true],
            ['foo ', 'foo ', true],

            ['foo', '{command}', true],
            ['foo ', '{command}', true],
            ['foo ', '{command} ', true],

            ['call me foo', 'call me {name}', true],
            ['call me foo ', 'call me {name}', true],
            ['call me foo ', 'call me {name} ', true],

            ['call me foo baz', 'call me {name} {surname}', true],
            ['call me foo baz ', 'call me {name} {surname}', true],
            ['call me foo baz ', 'call me {name} {surname} ', true],

            ['/foo', '/foo', true],
            ['/foo ', '/foo', true],
            ['/foo ', '/foo ', true],

            ['/foo', '/{command}', true],
            ['/foo ', '/{command}', true],
            ['/foo ', '/{command} ', true],

            ['/call me foo', '/call me {name}', true],
            ['/call me foo ', '/call me {name}', true],
            ['/call me foo ', '/call me {name} ', true],

            ['/call me foo baz', '/call me {name} {surname}', true],
            ['/call me foo baz ', '/call me {name} {surname}', true],
            ['/call me foo baz ', '/call me {name} {surname} ', true],

            ['!foo', '!foo', true],
            ['!foo ', '!foo', true],
            ['!foo ', '!foo ', true],

            ['!foo', '!{command}', true],
            ['!foo ', '!{command}', true],
            ['!foo ', '!{command} ', true],

            ['!call me foo', '!call me {name}', true],
            ['!call me foo ', '!call me {name}', true],
            ['!call me foo ', '!call me {name} ', true],

            ['!call me foo baz', '!call me {name} {surname}', true],
            ['!call me foo baz ', '!call me {name} {surname}', true],
            ['!call me foo baz ', '!call me {name} {surname} ', true],

            ['@foo', '@foo', true],
            ['@foo ', '@foo', true],
            ['@foo ', '@foo ', true],

            ['@foo', '@{command}', true],
            ['@foo ', '@{command}', true],
            ['@foo ', '@{command} ', true],

            ['@call me foo', '@call me {name}', true],
            ['@call me foo ', '@call me {name}', true],
            ['@call me foo ', '@call me {name} ', true],

            ['@call me foo baz', '@call me {name} {surname}', true],
            ['@call me foo baz ', '@call me {name} {surname}', true],
            ['@call me foo baz ', '@call me {name} {surname} ', true],

            ['#foo', '#foo', true],
            ['#foo ', '#foo', true],
            ['#foo ', '#foo ', true],

            ['#foo', '#{command}', true],
            ['#foo ', '#{command}', true],
            ['#foo ', '#{command} ', true],

            ['#call me foo', '#call me {name}', true],
            ['#call me foo ', '#call me {name}', true],
            ['#call me foo ', '#call me {name} ', true],

            ['#call me foo baz', '#call me {name} {surname}', true],
            ['#call me foo baz ', '#call me {name} {surname}', true],
            ['#call me foo baz ', '#call me {name} {surname} ', true],

            ['!@#2f00', '!@#2f00', true],
            ['!@#2f00 ', '!@#2f00', true],
            ['!@#2f00 ', '!@#2f00 ', true],

            ['!@#2c@ll m3 f00', '!@#2c@ll m3 {nam3}', true],
            ['!@#2c@ll m3 f00 ', '!@#2c@ll m3 {nam3}', true],
            ['!@#2c@ll m3 f00 ', '!@#2c@ll m3 {nam3} ', true],

            ['!@#2c@ll m3 f00 baz', '!@#2c@ll m3 {nam3} {surnam3}', true],
            ['!@#2c@ll m3 f00 baz ', '!@#2c@ll m3 {nam3} {surnam3}', true],
            ['!@#2c@ll m3 f00 baz ', '!@#2c@ll m3 {nam3} {surnam3} ', true],

            [' foo', 'foo', false],
            [' foo', 'foo ', false],
            [' foo ', 'foo ', false],

            ['foo', 'baz', false],
            ['foo ', 'baz', false],
            ['foo ', 'baz ', false],
        ];

        return $messages;
    }

    /**
     * @dataProvider incoming_message_provider
     * @dataProvider
     *
     * @param string $message
     * @param string $pattern
     * @param bool   $expected
     */
    public function test_is_pattern_valid(string $message, string $pattern, bool $expected)
    {
        $matcher = new Matcher();
        $incomingMessage = new IncomingMessage($message, 'bar', 'baz');
        $answer = new Answer();

        $this->assertSame($expected, $matcher->isPatternValid($incomingMessage, $answer, $pattern),
            sprintf('Message `%s` and pattern `%s` should assert `%s`',
                $message,
                $pattern,
                $expected ? 'true' : 'false')
        );
    }
}

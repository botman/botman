<?php

namespace BotMan\BotMan\tests\Messages;

use BotMan\BotMan\Messages\Attachments\Image;
use PHPUnit\Framework\TestCase;

class AttachmentTest extends TestCase
{
    /** @test */
    public function it_can_set_and_get_extras()
    {
        //Create an Image
        $attachment = new Image('foo');

        // Test adding an extra then getting it
        $attachment->addExtras('foo', [1, 2, 3]);
        $this->assertSame([1, 2, 3], $attachment->getExtras('foo'));

        // Test getting a non-existent extra
        $this->assertNull($attachment->getExtras('DoesNotExist'));
    }
}

<?php

namespace BotMan\BotMan\Tests\Fixtures;

class AnotherDriver extends TestDriver
{
    /**
     * Return the driver name.
     *
     * @return string
     */
    public function getName()
    {
        return 'Another';
    }
}

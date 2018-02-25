<?php

namespace BotMan\BotMan\tests;

use BotMan\BotMan\Http\Curl;
use PHPUnit\Framework\TestCase;
use BotMan\BotMan\Drivers\NullDriver;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Tests\Fixtures\TestDriver;
use BotMan\BotMan\Tests\Fixtures\AnotherDriver;
use BotMan\BotMan\Tests\Fixtures\TestDriverWithSubDriver;

class DriverManagerTest extends TestCase
{
    protected function tearDown()
    {
        DriverManager::unloadDriver(TestDriver::class);
        DriverManager::unloadDriver(TestDriverWithSubDriver::class);
        DriverManager::unloadDriver(AnotherDriver::class);
        \Mockery::close();
    }

    /** @test */
    public function it_can_be_created()
    {
        $driverManager = new DriverManager([], new Curl());
        $this->assertInstanceOf(DriverManager::class, $driverManager);
    }

    /** @test */
    public function it_can_get_available_drivers()
    {
        $this->assertTrue(is_array(DriverManager::getAvailableDrivers()));
    }

    /** @test */
    public function it_can_load_custom_drivers()
    {
        $count = count(DriverManager::getAvailableDrivers());
        DriverManager::loadDriver(TestDriver::class);
        $this->assertSame($count + 1, count(DriverManager::getAvailableDrivers()));
    }

    /** @test */
    public function it_can_load_custom_child_drivers()
    {
        $count = count(DriverManager::getAvailableDrivers());
        DriverManager::loadDriver(TestDriverWithSubDriver::class);
        $this->assertSame($count + 2, count(DriverManager::getAvailableDrivers()));
    }

    /** @test */
    public function it_only_loads_drivers_once()
    {
        $count = count(DriverManager::getAvailableDrivers());
        DriverManager::loadDriver(TestDriver::class);
        DriverManager::loadDriver(TestDriver::class);
        DriverManager::loadDriver(TestDriverWithSubDriver::class);
        $this->assertSame($count + 2, count(DriverManager::getAvailableDrivers()));
    }

    /** @test */
    public function it_loads_drivers_extensions()
    {
        DriverManager::loadDriver(TestDriver::class);
        $this->assertTrue($_SERVER['loadedTestDriver']);
    }

    /** @test */
    public function it_loads_custom_drivers_first()
    {
        DriverManager::loadDriver(TestDriver::class);
        $available = DriverManager::getAvailableDrivers();

        $this->assertSame(TestDriver::class, $available[0]);
    }

    /** @test */
    public function it_can_load_custom_drivers_from_name()
    {
        DriverManager::loadDriver(TestDriver::class);
        $this->assertInstanceOf(TestDriver::class, DriverManager::loadFromName('Test', []));
        $this->assertInstanceOf(TestDriver::class, DriverManager::loadFromName(TestDriver::class, []));

        // This driver has the characters 'e' and 'r' from 'Driver' charset in the old rtrim way of fetching the driver's name
        DriverManager::loadDriver(AnotherDriver::class);
        $this->assertInstanceOf(AnotherDriver::class, DriverManager::loadFromName('Another', []));
        $this->assertInstanceOf(AnotherDriver::class, DriverManager::loadFromName(AnotherDriver::class, []));
    }

    /** @test */
    public function it_can_find_a_driver_by_name()
    {
        $this->assertInstanceOf(NullDriver::class, DriverManager::loadFromName('foo', []));
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_can_get_configured_drivers()
    {
        $this->assertCount(0, DriverManager::getConfiguredDrivers([]));
    }
}

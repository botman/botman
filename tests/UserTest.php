<?php

namespace BotMan\BotMan\tests;

use BotMan\BotMan\Users\User;
use PHPUnit_Framework_TestCase;

class UserTest extends PHPUnit_Framework_TestCase
{
    private $userId = 'U023BECGF';
    private $userFirstName = 'Bobby';
    private $userLastName = 'Tables';
    private $userUsername = 'bobby';
    private $userUserInfos = [
        'id' => 'U023BECGF',
        'name' => 'bobby',
        'deleted' => false,
        'color' => '9f69e7',
        'profile' => [
            'avatar_hash' => 'ge3b51ca72de',
            'current_status' => ':mountain_railway => riding a train',
            'first_name' => 'Bobby',
            'last_name' => 'Tables',
            'real_name' => 'Bobby Tables',
            'tz' => 'America\/Los_Angeles',
            'tz_label' => 'Pacific Daylight Time',
            'tz_offset' => -25200,
            'email' => 'bobby@slack.com',
            'skype' => 'my-skype-name',
            'phone' => '+1 (123) 456 7890',
            'image_24' => 'https:\/\/...',
            'image_32' => 'https:\/\/...',
            'image_48' => 'https:\/\/...',
            'image_72' => 'https:\/\/...',
            'image_192' => 'https:\/\/...',
        ],
        'is_admin' => true,
        'is_owner' => true,
        'updated' => 1490054400,
        'has_2fa' => true,
    ];

    private function getTestUser()
    {
        $user = new User($this->userId, $this->userFirstName, $this->userLastName, $this->userUsername, $this->userUserInfos);
        return $user;
    }

    /** @test */
    public function it_can_get_user_id()
    {
        $user = $this->getTestUser();
        $this->assertSame($this->userId, $user->getId());
    }

    /** @test */
    public function it_can_get_user_firstname()
    {
        $user = $this->getTestUser();
        $this->assertSame($this->userFirstName, $user->getFirstName());
    }

    /** @test */
    public function it_can_get_user_lastname()
    {
        $user = $this->getTestUser();
        $this->assertSame($this->userLastName, $user->getLastName());
    }

    /** @test */
    public function it_can_get_user_username()
    {
        $user = $this->getTestUser();
        $this->assertSame($this->userUsername, $user->getUsername());
    }

    /** @test */
    public function it_can_get_user_userinfos()
    {
        $user = $this->getTestUser();
        $this->assertSame($this->userUserInfos, $user->getInfo());
    }
}

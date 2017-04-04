<?php

namespace Mpociot\BotMan\UserInfo;

use Slack\User;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Interfaces\UserInfoInterface;

class SlackRTMUserInfo implements UserInfoInterface
{
    /** @var User */
    protected $user;

    /**
     * SlackRTMUserInfo Constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $info = null;

        Collection::make([
            'get'.ucfirst($key),
            'is'.ucfirst($key),
            $key,
        ])
        ->each(function ($method) use (&$info) {
            if (method_exists($this->user, $method)) {
                $info = $this->user->$method();
            }
        });

        return $info;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return $this->user;
    }
}

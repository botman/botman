<?php

namespace BotMan\BotMan\Tests\Fixtures;

use BotMan\BotMan\Messages\Attachments\Contact;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;

class TestDataConversation extends Conversation
{
    /**
     * @return mixed
     */
    public function run()
    {
        $this->ask('What do you want to test?', function (Answer $answer) {
            if ($answer->getText() === 'images') {
                $this->askForImages('Please supply an image', function ($images) {
                    $this->say($images[0]);
                });
            } elseif ($answer->getText() === 'custom_image_repeat') {
                $this->askForImages('Please supply an image', function ($images) {
                    $this->say($images[0]);
                }, function (Answer $answer) {
                    $this->say('That is not an image...');
                });
            } elseif ($answer->getText() === 'videos') {
                $this->askForVideos('Please supply a video', function ($images) {
                    $this->say($images[0]);
                });
            } elseif ($answer->getText() === 'custom_video_repeat') {
                $this->askForVideos('Please supply a video', function ($images) {
                    $this->say($images[0]);
                }, function (Answer $answer) {
                    $this->say('That is not a video...');
                });
            } elseif ($answer->getText() === 'audio') {
                $this->askForAudio('Please supply an audio', function ($images) {
                    $this->say($images[0]);
                });
            } elseif ($answer->getText() === 'custom_audio_repeat') {
                $this->askForAudio('Please supply an audio', function ($images) {
                    $this->say($images[0]);
                }, function (Answer $answer) {
                    $this->say('That is not an audio...');
                });
            } elseif ($answer->getText() === 'location') {
                $this->askForLocation('Please supply a location', function (Location $location) {
                    $this->say($location->getLatitude().':'.$location->getLongitude());
                });
            } elseif ($answer->getText() === 'custom_location_repeat') {
                $this->askForLocation('Please supply a location', function (Location $location) {
                    $this->say($location->getLatitude().':'.$location->getLongitude());
                }, function (Answer $answer) {
                    $this->say('That is not a location...');
                });
            } elseif ($answer->getText() === 'contact') {
                $this->askForContact('Please supply a contact', function (Contact $contact) {
                    $this->say($contact->getPhoneNumber().':'.$contact->getFirstName().':'.$contact->getLastName().':'.$contact->getUserId());
                });
            } elseif ($answer->getText() === 'custom_contact_repeat') {
                $this->askForContact('Please supply a contact', function (Contact $contact) {
                    $this->say($contact->getPhoneNumber().':'.$contact->getFirstName().':'.$contact->getLastName().':'.$contact->getUserId());
                }, function (Answer $answer) {
                    $this->say('That is not a contact...');
                });
            }
        });
    }

    protected function _throwException($message)
    {
        throw new \Exception($message);
    }
}

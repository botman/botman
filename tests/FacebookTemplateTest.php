<?php

namespace Mpociot\BotMan\Tests;

use Mpociot\BotMan\Facebook\Element;
use Mpociot\BotMan\Facebook\ElementButton;
use Mpociot\BotMan\Facebook\GenericTemplate;
use PHPUnit_Framework_TestCase;

class FacebookTemplateTest extends PHPUnit_Framework_TestCase
{

    /** @test */
    public function it_can_be_created()
    {
        $template = new GenericTemplate('');
        $this->assertInstanceOf(GenericTemplate::class, $template);
    }

    /** @test */
    public function it_can_set_template_type()
    {
        $template = new GenericTemplate('list');

        $expectedArray = [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'list',
                    'elements' =>[]
                ],
                'buttons' => []
            ]
        ];

        $this->assertSame($expectedArray, $template->toArray());
    }

    /** @test */
    public function it_can_set_top_element_style()
    {
        $template = new GenericTemplate('generic');
        $template->useCompactView();

        $expectedArray = [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'generic',
                    'elements' =>[],
                    'top_element_style' => 'compact',
                ],
                'buttons' => []
            ]
        ];

        $this->assertSame($expectedArray, $template->toArray());
    }

    /**
     * @test
     **/
    public function it_can_add_an_element()
    {
        $template = new GenericTemplate('generic');
        $template->addElement(Element::create('element'));

        $expectedArray = [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'generic',
                    'elements' =>[
                        [
                            'title' => 'element',
                            'image_url' => null,
                            'item_url' => null,
                            'subtitle' => null,
                            'buttons' => null,
                        ]
                    ]
                ],
                'buttons' => []
            ]
        ];

        $this->assertSame($expectedArray, $template->toArray());
    }
    
    /**
     * @test
     **/
    public function it_can_add_add_global_list_button()
    {
        $template = new GenericTemplate('generic');
        $template->addGlobalButton(ElementButton::create('Global button')->url('http://test.at'));

        $expectedArray = [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'generic',
                    'elements' =>[]
                ],
                'buttons' => [
                    'type' => 'web_url',
                    'url' => 'http://test.at',
                    'title' => 'Global button',
                ]
            ]
        ];

        $this->assertEquals($expectedArray, $template->toArray());
    }
}
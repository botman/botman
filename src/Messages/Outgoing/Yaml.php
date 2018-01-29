<?php

namespace BotMan\BotMan\Messages\Outgoing;

use Illuminate\Support\Collection;
use Mustache_Engine;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

class Yaml
{
    /** @var Collection */
    protected $contents;

    /**
     * Yaml constructor.
     * @param $file
     * @throws \Exception
     */
    public function __construct($file)
    {
        if (!file_exists($file)) {
            throw new \Exception('File "'.$file.'" does not exist."');
        }
        $this->contents = file_get_contents($file);
    }

    /**
     * @param $input
     * @param array $data
     * @return Collection
     */
    protected function parse($input, $data = [])
    {
        $yaml = $this->parseMustacheTemplate($input, $data);
        $parsed = SymfonyYaml::parse($yaml);

        return Collection::make($parsed);
    }

    /**
     * @param string $content
     * @param array $data
     * @return mixed
     */
    public function getMessagesForContent(string $content, $data = [])
    {
        $block = $this->parse($this->contents, $data)->get($content, []);
        return $this->mapInstructions($block, $data);
    }

    /**
     * @param $text
     * @param array $data
     * @return string
     */
    protected function parseMustacheTemplate($text, $data = [])
    {
        return (new Mustache_Engine)->render($text, $data);
    }

    /**
     * @param array $block
     * @param array $data
     * @return array
     */
    protected function mapInstructions(array $block, $data = [])
    {
        return Collection::make($block)->map(function ($instruction) use ($data) {
            $result = [];

            if (is_array($instruction)) {
                $instruction = Collection::make($instruction);
                if ($instruction->has('text')) {
                    $text = $instruction->get('text');

                    $result[] = [
                        'method' => (is_array($text)) ? 'randomReply' : 'reply',
                        'arguments' => [
                            is_array($text) ? array_map([$this, 'parseMustacheTemplate'], $text, $data) : $this->parseMustacheTemplate($text, $data)
                        ]
                    ];
                }
                if ($instruction->has('typing')) {
                    $result[] = [
                        'method' => 'typesAndWaits',
                        'arguments' => [
                            $instruction->get('wait', 1)
                        ]
                    ];
                }
            }

            if (is_string($instruction)) {
                $result[] = [
                    'method' => 'reply',
                    'arguments' => [
                        $this->parseMustacheTemplate($instruction, $data)
                    ]
                ];
            }
            return $result;
        })
            ->flatten(1)
            ->toArray();
    }

}
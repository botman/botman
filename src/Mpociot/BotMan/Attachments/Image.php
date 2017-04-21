<?php

namespace Mpociot\BotMan\Attachments;


class Image extends Attachment {

	/** @var string */
	protected $url;

	/**
	 * Video constructor.
	 * @param string $url
	 * @param mixed $payload
	 */
	public function __construct($url, $payload = null) {
		parent::__construct($payload);
		$this->url = $url;
	}

	/**
	 * @param $url
	 * @return Image
	 */
	public static function url($url){
		return new self($url);
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}
}
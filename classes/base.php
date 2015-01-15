<?php namespace Classes;

/**
 * @Author: mark
 * @Date:   2015-01-13 12:45:16
 * @Last Modified by:   mark
 * @Last Modified time: 2015-01-15 13:07:05
 */

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class Base
{
	public function __construct()
	{
		$this->client = new Client();
		$this->cookies =  new CookieJar();

		$this->cache = new \Redis();
		$this->cache->connect(getenv('cache'));
	}
}

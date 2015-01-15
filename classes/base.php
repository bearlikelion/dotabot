<?php
/**
 * @Author: mark
 * @Date:   2015-01-13 12:45:16
 * @Last Modified by:   mark
 * @Last Modified time: 2015-01-13 14:01:57
 */

use GuzzleHttp\Client;

class Base
{
	public function __construct()
	{
		$this->client = new Client();
		$this->cache = new Redis();
		$this->cache->connect(getenv('cache'));
	}
}

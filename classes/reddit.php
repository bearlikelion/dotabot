<?php namespace Classes;

/**
 * @Author: mark
 * @Date:   2015-01-13 12:45:16
 * @Last Modified by:   mark
 * @Last Modified time: 2016-05-18 10:54:38
 */

class Reddit extends Base
{
	public $modhash;

	public function login()
	{
		$loginRequest = $this->client->post('https://api.reddit.com/api/login', [
			'headers' => [
				'User-Agent' => 'r/dota2 sidebar bot (by /u/m4rx)'
			],
			'form_params' => [
				'api_type' => 'json',
				'user' => getenv('username'),
				'passwd' => getenv('password')
			],
			'cookies' => $this->cookies
		]);

		$response = json_decode($loginRequest->getBody()->getContents());

		if ($this->modhash = $response->json->data->modhash)
		{
			return true;
		}

		return false;
	}

	public function getSettings($subreddit)
	{
		$settings = $this->client->get('https://api.reddit.com/r/'.$subreddit.'/about/edit.json', [
			'headers' => [
				'User-Agent' => 'r/dota2 sidebar bot (by /u/m4rx)'
			],
			'cookies' => $this->cookies
		]);

		$settings = json_decode($settings->getBody()->getContents(), true)['data'];

		$settings['type'] = 'public';
		$settings['link_type'] = 'any';
		$settings['api_type'] = 'json';
		$settings['allow_top'] = true;
		$settings['uh'] = $this->modhash;
		$settings['sr'] = $settings['subreddit_id'];

		return $settings;
	}

	public function postSettings($settings)
	{
		$response = $this->client->post('https://api.reddit.com/api/site_admin', [
			'headers' => [
				'User-Agent' => 'r/dota2 sidebar bot (by /u/m4rx)'
			],
			'form_params' => $settings,
			'cookies' => $this->cookies
		]);

		return $response->getBody()->getContents();
	}
}

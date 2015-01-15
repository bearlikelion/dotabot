<?php namespace Classes;

/**
 * @Author: mark
 * @Date:   2015-01-13 12:45:16
 * @Last Modified by:   mark
 * @Last Modified time: 2015-01-15 13:41:38
 */

class Reddit extends Base
{
	public $modhash;

	public function login()
	{
		$loginRequest = $this->client->post('https://api.reddit.com/api/login', [
			'body' => [
				'api_type' => 'json',
				'user' => getenv('username'),
				'passwd' => getenv('password')
			],
			'cookies' => $this->cookies
		]);

		$response = $loginRequest->json();

		if ($this->modhash = $response['json']['data']['modhash'])
		{
			return true;
		}

		return false;
	}

	public function getSettings($subreddit)
	{
		$settings = $this->client->get('https://api.reddit.com/r/'.$subreddit.'/about/edit.json', [
			'cookies' => $this->cookies
		]);

		$settings = $settings->json()['data'];

		$settings['type'] = 'public';
		$settings['link_type'] = 'any';
		$settings['api_type'] = 'json';
		$settings['uh'] = $this->modhash;
		$settings['sr'] = $settings['subreddit_id'];

		return $settings;
	}

	public function postSettings($settings)
	{
		$response = $this->client->post('https://api.reddit.com/api/site_admin', [
			'body' => $settings,
			'cookies' => $this->cookies
		]);

		return $response->json();
	}
}

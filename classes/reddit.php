<?php
/**
 * @Author: mark
 * @Date:   2015-01-13 12:45:16
 * @Last Modified by:   mark
 * @Last Modified time: 2015-01-13 16:01:42
 */

class Reddit extends Base
{
	public function login()
	{
		// if (!$this->cache->exists('rdota2_modhash'))
		// {
			$loginRequest = $this->client->post('https://api.reddit.com/api/login', [
				'body' => [
					'api_type' => 'json',
					'user' => getenv('username'),
					'passwd' => getenv('password')
				]
			]);

			$response = json_decode($loginRequest->getBody()->getContents());

			if ($response)
			{
				d($response);
				$this->cache->set('rdota2_modhash', $response->json->data->modhash);
				$this->cache->expire('rdota2_modhash', 3600);

				$this->cache->set('rdota2_session', $response->json->data->cookie);
				$this->cache->expire('rdota2_session', 3600);

				return $response->json->data->cookie;
			}
		// } else return $this->cache->get('rdota2_modhash');

		return false;
	}
}

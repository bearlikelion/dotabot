<?php
namespace  Dota;
Class Bot {
	public function __construct() {
		require 'config.php';

		$this->limit = 6;
		$this->cache = new \Predis\Client;
	}

	public function update() {
		// $uh = $this->login();
		 $events = $this->events();
	}

	public function login() {
		$response = $this->reddit_api('login', $this->User);
		return $response['modhash'];
	}

	public function events() {
		if (is_null($this->cache->get('events')))  {
			$events['jd'] = $this->joinDOTA();
			$events['gg'] = $this->gosugamers();

			$events = json_encode($events);

			$this->cache->set('events', $events);
		} else $events = $this->cache->get('events');

		$this->sort(json_decode($events, true));
	}

	public function sort($events = NULL) {
		if (!is_null($events))
		{
			foreach ($events['gg'] as $key => $value) $events['gg'][$key]['match_time'] = strtotime($value['datetime']);

			// jD's API often returns NULL, maybe i'm being throttled; or they still have that template FATAL ERROR on their end
			if (!is_null($events['jd'])) {
				$matches = array_merge($events['jd'], $events['gg']);

				usort($matches, function($key1, $key2) {
					$value1 = $key1['match_time'];
					$value2 = $key2['match_time'];
					return $value1 - $value2;
				});


				$this->parse($matches);
			}
		}
	}

	public function parse($matches) {
		print(json_encode($matches));
	}

	protected function joinDOTA() {
		$data = file_get_contents($this->API['jd']);
		$data = json_decode($data);
		return $data;
	}

	protected function gosugamers() {
		$data = file_get_contents($this->API['gg']);
		$data = json_decode($data);
		return $data->matches;
	}

	private function reddit_api($controller,  $parameters, $method = 'POST') {
		$parameters['api_type'] = 'json';
		$endpoint = 'http://api.reddit.com/api/'.$controller;

		$curl = curl_init();
		$options = array(
			CURLOPT_URL => $endpoint,
			CURLOPT_RETURNTRANSFER => true,
		);

		if ($method == 'POST')
		{
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = $parameters;
		}

		curl_setopt_array($curl, $options);
		$response = json_decode(curl_exec($curl), true);
		curl_close($curl);

		if (!isset($response['json']['error'])) return $response['json']['data'];
	}
}
?>
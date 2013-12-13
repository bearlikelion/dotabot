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
		//if (is_null($this->cache->get('events')))  {
			$events['jd'] = $this->joinDOTA();
			$events['gg'] = $this->gosugamers();

			$events = json_encode($events);

			$this->cache->set('events', $events);
		//} else $events = $this->cache->get('events');

		$this->sort(json_decode($events, true));
	}

	public function sort($events = NULL) {
		if (!is_null($events))
		{
			foreach ($events['gg'] as $key => $value) $events['gg'][$key]['match_time'] = strtotime($value['datetime']);

			// jD's API often returns NULL
			if (!is_null($events['jd']) && $events['jd'] != 'NULL') {
				$matches = array_merge($events['jd'], $events['gg']);

				usort($matches, function($key1, $key2) {
					$value1 = $key1['match_time'];
					$value2 = $key2['match_time'];
					return $value2 - $value1;
				});

				$this->parse($matches);
			}
		}
	}

	public function parse($matches) {
		print'<pre>';
		print_r($matches);
		print'</pre>';
		exit;

		$i = 0;
		date_default_timezone_set('UTC');
		foreach ($matches as $match) {
			//if ($i < $this->limit) {
				// Time
				$ticker[$i]['timestamp'] = $match['match_time'];
				$total_time = time() - $match['match_time'];
				$days       = floor($total_time /86400);
				$hours      = floor($total_time /3600);
				$minutes    = intval(($total_time/60) % 60);
				$time = "";
				if($days > 0) $time .= $days . 'd ';
				else if($hours > 0) $time .= $hours.'h ';
				else if($minutes > 0) $time .= $minutes.'m';
				else $time .= 'live';

				$ticker[$i]['time'] = $time;

				// tournament name
				if (isset($match['coverage_title'])) $ticker[$i]['tournament'] = $match['coverage_title'];
				else if (isset($match['tournament']['name'])) $ticker[$i]['tournament'] = $match['tournament']['name'];

				// Teams
				if (isset($match['team_1_name']) && isset($match['team_2_name']))  $ticker[$i]['teams'] = $match['team_1_name'].' vs '.$match['team_2_name'];
				else if (isset($match['firstOpponent']['name']) && isset($match['secondOpponent']['name']))  $ticker[$i]['teams'] = $match['firstOpponent']['name'].' vs '.$match['secondOpponent']['name'];

				// URLs
				if (isset($match['pageUrl'])) $ticker[$i]['url']['gg'] = $match['pageUrl'];
				if (isset($match['coverage_url'])) $ticker[$i]['url']['jd'] = $match['coverage_url'];

				$i++;
			//} else break;
		}

		print'<pre>';
		print_r($ticker);
		print'</pre>';
	}

	public function format($ticker) {
		// TO DO: Format ticker array into reddit markdown
	}

	public function post($text) {
		// TO DO: Post $text to reddit
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
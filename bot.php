<?php
namespace  Dota;
Class Bot {		
	public function __construct() {	
		require 'config.php';
		$this->redis = new \Predis\Client;
	}

	public function update()
	{
		// $uh = $this->login();
		$events = $this->events();
	}

	public function login()
	{
		$response = $this->reddit_api('login', $this->User);
		return $response['modhash'];
	}

	public function events()
	{
		$events[] = $this->joinDOTA();
		$events[] = $this->gosugamers();

		var_dump($events);
	}

	protected function joinDOTA() {
		$data = file_get_contents($this->API['jd']);
		$data = json_decode($data);
		return $data;
	}

	protected function gosugamers() {
		$data = file_get_contents($this->API['gg']);
		$data = json_decode($data);
		return $data;
	}

	private function reddit_api($controller,  $parameters, $method = 'POST')
	{
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
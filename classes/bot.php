<?php
/**
 * @Author: mark
 * @Date:   2015-01-13 12:45:16
 * @Last Modified by:   mark
 * @Last Modified time: 2015-01-15 12:03:57
 */
class Bot
{
	private $matchLimit = 6;

	public function __construct()
	{
		$this->base = new Base();
		$this->reddit = new Reddit();
		$this->url = 'http://gosugamers.net/api/matches?game=dota2&apiKey='.getenv('ggKey');
	}

	public function updateSidebar()
	{
		$jar = new GuzzleHttp\Cookie\CookieJar();

		$loginRequest = $this->base->client->post('https://api.reddit.com/api/login', [
			'body' => [
				'api_type' => 'json',
				'user' => getenv('username'),
				'passwd' => getenv('password')
			],
			'cookies' => $jar
		]);

		dd($jar);

		try {
			$request = $this->base->client->get('http://reddit.com/r/dota2/about/edit.json', ['cookies' => $jar]);
		} catch (Exception $e) {
			d($e);
			dd($request);
		}

		dd($settings);
		$sidebar = json_decode(file_get_contents('http://reddit.com/r/dota2/wiki/sidebar.json'));
		$gosuGames = json_decode(file_get_contents($this->url));
		$matches = $this->formatMatches($gosuGames);
	}

	public function formatMatches($matches)
	{
		$text = '';
		$index = 0;
		foreach ($matches->matches as $match)
		{
			if ($index < $this->matchLimit)
			{
				d($match);
				$url = $this->_cacheUrl($match->pageUrl);

				$timestamp = strtotime($match->datetime);
				$timeTill = $this->_timeTill($timestamp);

				if ($match->isLive) $text .= '* [**LIVE - ' . $match->tournament->name . '**]';
				else $text .= '* ['.$timeTill.' - ' . $match->tournament->name . ']';
				$text .= '('.$url.' "' . date('M d H:m T', $timestamp) ."\")\n";


				$text .= '[](/'.strtolower($match->firstOpponent->country->countryCode).') ' . $match->firstOpponent->name . ' vs ' . '[](/'.strtolower($match->secondOpponent->country->countryCode).') ' . $match->secondOpponent->name . "\n\n";

				$index++;
			} else break;
		}
	}

	private function _cacheUrl($url)
	{
		$shortUrl = substr(md5($url), 4, 5);
		$this->cache->set($shortUrl, $url);

		return 'http://rdota2.com/m/'.$shortUrl;
	}

	private function _timeTill($time)
	{
		$_time = '';
		$total_time = time() - $time;
		if ($total_time < 0) $total_time = $time - time();

		$days       = floor($total_time /86400);
		$hours      = intval(($total_time /3600) % 24);
		$minutes    = intval(($total_time/60) % 60);

		if($days > 0) $_time .= $days . 'd ';

		if ($hours > 0) {
			if ($hours > 24) $hours = $hours - 24;
			$_time .= $hours.'h ';
		}

		if ($minutes > 0) $_time .= $minutes.'m';

		return $_time;
	}
}

<?php
namespace Dota;

class Bot
{
	public function __construct()
	{
		ini_set('user_agent', '/r/dota2 status checker');

		require 'config.php';
		require 'lib/googl.php';
		require 'lib/snoopy.php';

		$this->limit = 6;
		$this->cache = new \Predis\Client();
		$this->snoopy = new \Snoopy();
	}

	public function update()
	{
		return $this->events();
	}

	public function login()
	{
		if ($this->cache->get('uh') && $this->cache->get('cookie')) $this->snoopy->cookies['reddit_session'] = $this->cache->get('cookie');
		else {
			$this->snoopy->submit("http://reddit.com/api/login/".$this->User['user'], $this->User);

			$login = json_decode($this->snoopy->results);
			$this->snoopy->cookies['reddit_session'] = $login->json->data->cookie;

			$this->cache->set('uh', $login->json->data->modhash);
			$this->cache->set('cookie', $login->json->data->cookie);
			$this->cache->expire('uh', 300);
			$this->cache->expire('cookie', 300);
		}
	}

	public function status()
	{
		// $gc = json_decode(file_get_contents('https://steamdb.info/api/SteamRailgun/'));
		$players = json_decode(file_get_contents('http://www.dota2.com/overview/externaldata/'));
		$return = '';
		// if (isset($gc->dota2->status) && $gc->dota2->status != 'good') $return .= "#### **[The Dota 2 Game Coordinator is Down](http://steamstat.us/)** ####\n\n";
		if (isset($players->rgPlayersTotal) && $players->rgPlayersTotal == 0) $return .= "##### **[The Dota 2 Network is Offline](http://dota2.com/overview/external)** #####\n";

		return $return;
	}

	public function events()
	{
		$events['gg'] = $this->gosugamers();

		$events = json_encode($events);
		$this->cache->set('events', $events);
		$this->cache->expire('events', 60);

		$this->sort(json_decode($events, true));
	}

	public function sort($events = null)
	{
		if (!is_null($events)) {
			foreach ($events['gg'] as $key => $value) $events['gg'][$key]['match_time'] = strtotime($value['datetime']);

			// jD's API often returns NULL
			if (isset($events['jd']) && !is_null($events['jd']) && $events['jd'] != 'NULL') {
				$matches = array_merge($events['jd'], $events['gg']);

				usort($matches, function ($key1, $key2) {
					$value1 = $key1['match_time'];
					$value2 = $key2['match_time'];
					return $value2 - $value1;
				});
			} else {
				usort($events['gg'], function ($key1, $key2) {
					$value1 = $key1['match_time'];
					$value2 = $key2['match_time'];
					return $value1 - $value2;
				});
				$matches = $events['gg'];
			}

			$this->parse($matches);
		}
	}

	public function parse($matches)
	{
		$i = 0;
		$ticker = array();
		require 'countries.php';
		foreach ($matches as $match) {
			if ($i < $this->limit) {
				// Time
				$time = "";
				// game hasn't started yet
				$ticker[$i]['timestamp'] = $match['match_time'];
				if (time() < $match['match_time']) {
					$total_time = time() - $match['match_time'];
					if ($total_time < 0) $total_time = $match['match_time'] - time();
					$days       = floor($total_time /86400);
					$hours      = intval(($total_time /3600) % 24);
					$minutes    = intval(($total_time/60) % 60);
					if($days > 0) $time .= $days . 'd ';
					if ($hours > 0) {
						if ($hours > 24) $hours = $hours - 24;
						$time .= $hours.'h ';
					}
					if ($minutes > 0) $time .= $minutes.'m';
				} else $time = 'live';

				$ticker[$i]['time'] = $time;

				// tournament name
				if (isset($match['coverage_title'])) $ticker[$i]['tournament'] = $match['coverage_title'];
				elseif (isset($match['tournament']['name'])) $ticker[$i]['tournament'] = $match['tournament']['name'];

				// Teams
				if (isset($match['team_1_name']) && isset($match['team_2_name']))  $ticker[$i]['teams'] = $match['team_1_name'].' vs '.$match['team_2_name'];
				elseif (isset($match['firstOpponent']['shortName']) && isset($match['secondOpponent']['shortName']))  $ticker[$i]['teams'] = '[](/'.strtolower($match['firstOpponent']['country']['countryCode']).' "'.$countries[$match['firstOpponent']['country']['countryCode']].'") '.$match['firstOpponent']['shortName'].' vs [](/'.strtolower($match['secondOpponent']['country']['countryCode']).' "'.$countries[$match['secondOpponent']['country']['countryCode']].'") '.$match['secondOpponent']['shortName'];

				// URLs
				if (isset($match['pageUrl'])) $ticker[$i]['url']['gg'] = $match['pageUrl'];
				if (isset($match['coverage_url'])) $ticker[$i]['url']['jd'] = $match['coverage_url'];

				$i++;
			} else break;
		}

		if (isset($_GET['die'])) {
			d($matches);
			dd($ticker);
		}

		$this->format($ticker);
	}

	/**
	 * Reindexes the Ticker based on the calculated 'time', placing live at the top and then ordering by hour/minute
	 * @param  array $ticker
	 * @return array
	 */
	public function sortTicker(&$ticker, $cmp_function)
	{
		$live = 0;
		foreach ($ticker as $tick) {
			// if ($tick['time'] == 'live')
		}
		return $ticker;
	}

	public function format($ticker)
	{
		$tock = "";
		foreach ($ticker as $tick) {
			if (isset($tick['url']['gg'])) {
				$url = $this->shortenUrl($tick['url']['gg']);
				if ($tick['time'] == 'live') $tock .= '* [**LIVE - ' . $tick['tournament'] . '**]('.$url.' "'.date('M d H:m T', $tick['timestamp']).'")  ';
				else $tock .= '* ['.$tick['time'].' - ' . $tick['tournament'] . ']('.$url.' "'.date('M d H:m T', $tick['timestamp']).'")  ';

				$tock .= "\n".$tick['teams']."\n\n";
			}
		}

		if (count($ticker) < $this->limit) {
			$i = 0;
			$_nulls = $this->limit - count($ticker);
			$twitch = json_decode(file_get_contents('https://api.twitch.tv/kraken/streams?limit=6&game=Dota+2'), true);
			$streams = $twitch['streams'];

			foreach ($streams as $stream) {
				if ($i < $_nulls) {
					$tock .= '* ['.$stream['channel']['display_name'] . ' ' . implode(' ', array_slice(explode(' ', $stream['channel']['status']), 0, 10));
					$tock .= ']('.$stream['channel']['url']." \"".$stream['viewers']." viewers\") [](/twitch) " . $stream['viewers']. " viewers\n\n";
					$i++;
				} else break;
			}
		}

		$this->prepare($tock);
	}

	public function prepare($text)
	{
		$status = $this->status();

		$json = json_decode(file_get_contents('http://www.reddit.com/r/dota2/wiki/sidebar.json'));
		$description = $json->data->content_md;
		$description = str_replace("&gt;", ">", $description);
		$description = str_replace('%%STATUS%%', $status, $description);
		$description = str_replace("%%EVENTS%%", $text, $description);

		$this->post($description);
	}

	protected function post($description)
	{
		$this->login();

		$this->snoopy->fetch('http://reddit.com/r/dota2/about/edit/.json');
		$about = json_decode($this->snoopy->results);
		if (isset($about->data)) {
			$data = $about->data;

			$parameters['sr'] = 't5_2s580';
			$parameters['title'] = $data->title;
			$parameters['public_description'] = $data->public_description;
			$parameters['lang'] = $data->language;
			$parameters['type'] = $data->subreddit_type;
			$parameters['link_type'] = $data->content_options;
			$parameters['wikimode'] = $data->wikimode;
			$parameters['wiki_edit_karma'] = $data->wiki_edit_karma;
			$parameters['wiki_edit_age'] = $data->wiki_edit_age;
			$parameters['allow_top'] = 'on';
			$parameters['header-title'] = '';
			$parameters['id'] = '#sr-form';
			$parameters['r'] = 'dota2';
			$parameters['renderstyle'] = 'html';
			$parameters['comment_score_hide_mins'] = $data->comment_score_hide_mins;
			$parameters['public_traffic'] = 'on';
			$parameters['spam_comments'] = 'low';
			$parameters['spam_links'] = 'high';
			$parameters['spam_selfposts'] = 'high';
			$parameters['link_type'] = 'any';
			$parameters['description'] = $description;
			$parameters['uh'] = $this->cache->get('uh');

			$this->snoopy->submit("http://www.reddit.com/api/site_admin?api_type=json", $parameters);
			print "\n\n" . date("[Y/M/d - H:i]: <br>") . $this->snoopy->results;
			print "\n<pre>".$description."</pre>";
		} else print 'Failed to fetch sidebar from reddit';
	}

	protected function gosugamers()
	{
		$data = file_get_contents($this->API['gg']);
		$data = json_decode($data);
		return $data->matches;
	}

	private function shortenUrl($url)
	{
		$googl = new \Googl($this->googleAPI);
		$short = $googl->shorten($url);

		//Return
		return $short;
	}
}

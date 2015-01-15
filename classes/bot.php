<?php namespace Classes;

/**
 * @Author: mark
 * @Date:   2015-01-13 12:45:16
 * @Last Modified by:   mark
 * @Last Modified time: 2015-01-15 13:46:38
 */

class Bot extends Base
{
	private $matchLimit = 6;
	private $url = 'http://gosugamers.net/api/matches?game=dota2&apiKey=';

	public function updateSidebar()
	{
		$Reddit = new Reddit();

		if ($Reddit->login())
		{
			$matches = $this->formatMatches($this->getGames());

			if ($matches != '')
			{
				$sidebar = json_decode(file_get_contents('http://reddit.com/r/dota2/wiki/sidebar.json'))->data->content_md;
				$sidebar = str_replace('%%EVENTS%%', $matches, $sidebar);

				$settings = $Reddit->getSettings('dota2');
				$settings['description'] = html_entity_decode($sidebar);

				if ($Reddit->postSettings($settings))
				{
					return $settings['description'];
				}
			}
		}

		return 'Failed to login to reddit';
	}

	public function formatMatches($matches)
	{
		$text = '';
		$index = 0;
		foreach ($matches->matches as $match)
		{
			if ($index < $this->matchLimit)
			{
				$url = $this->_cacheUrl($match->pageUrl);

				$timestamp = strtotime($match->datetime);
				$timeTill = $this->_timeTill($timestamp);

				if ($match->isLive)
				{
					$text .= '* [**LIVE - ' . $match->tournament->name . '**]';
				}
				else
				{
					$text .= '* ['.$timeTill.' - ' . $match->tournament->name . ']';
				}

				$text .= '('.$url.' "' . date('M d H:m T', $timestamp) ."\")\n";
				$text .= '[](/'.strtolower($match->firstOpponent->country->countryCode).') ' . $match->firstOpponent->name . ' vs ' . '[](/'.strtolower($match->secondOpponent->country->countryCode).') ' . $match->secondOpponent->name;

				if ($index < ($this->matchLimit - 1))
				{
					$text .= "\n\n";
				}

				$index++;
			} else break;
		}

		return $text;
	}

	public function getGames()
	{
		if ($this->cache->exists('gosugamers_games'))
		{
			$games = $this->cache->get('gosugamers_games');
			return json_decode($games);
		}

		$games = file_get_contents($this->url.getenv('ggKey'));
		$this->cache->set('gosugamers_games', $games);
		$this->cache->expire('gosugamers_games', 1800); // Cahce games for 30m

		return json_decode($games);
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

		if ($total_time < 0)
		{
			$total_time = $time - time();
		}

		$days = floor($total_time /86400);
		$hours = intval(($total_time /3600) % 24);
		$minutes = intval(($total_time/60) % 60);

		if($days > 0)
		{
			$_time .= $days . 'd ';
		}

		if ($hours > 0)
		{
			if ($hours > 24)
			{
				$hours = $hours - 24;
			}

			$_time .= $hours.'h ';
		}

		if ($minutes > 0)
		{
			$_time .= $minutes.'m';
		}

		return $_time;
	}
}

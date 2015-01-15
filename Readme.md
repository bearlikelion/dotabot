# r/dota2's sidebar match ticker
###Written by [Mark Arneman](http://arneman.me) "[/u/m4rx](http://reddit.com/u/m4rx)"

This is a simple script written using the [Slim Framework](http://slimframework.com) used to update r/dota2's sidebar with Dota 2 matches from the [GosuGamers](http://gosugamers.com) API.  This is intended to be educational and instructional only, the script itself requires a private API key from GosuGamers in order to properly function.

## Requirements
* Composer
* PHP 5.4+
* Cron

## Installation
```
# Install Composer
git clone https://github.com/bearlikelion/dotabot.git
composer install
```
Create a cronjob to process the index.php every X minutes.
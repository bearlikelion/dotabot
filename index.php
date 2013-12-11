<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	date_default_timezone_set('UTC');
	require 'vendor/autoload.php';
	require 'bot.php';

	$app = new \Slim\Slim;

	$app->get('/', function() {
		$Bot = new \Dota\Bot;
		$Bot->update();
	});

	$app->run();
?>
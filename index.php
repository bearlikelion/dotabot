<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	require 'vendor/autoload.php';
	require 'bot.php';

	$app = new \Slim\Slim;

	$app->get('/', function() {
		$Bot = new \Dota\Bot;
		$Bot->update();
	});

	$app->run();
?>
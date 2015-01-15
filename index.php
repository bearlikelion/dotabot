<?php
/**
 * @Author: mark
 * @Date:   2015-01-13 12:45:16
 * @Last Modified by:   mark
 * @Last Modified time: 2015-01-15 13:46:29
 */

require 'vendor/autoload.php';

Dotenv::load(__DIR__); // load .env config file

$app = new \Slim\Slim();

$app->setName('rdota2.com');

$app->get('/', function () {
	print (new Classes\Bot())->updateSidebar();
});

$app->get('/m/:id', function($id) use ($app) {
	$cache = new Redis();
	$cache->connect('127.0.0.1');

	$url = $cache->get($id);
	$app->redirect($url);
});

$app->run();

<?php
/**
 * @Author: mark
 * @Date:   2015-01-13 12:45:16
 * @Last Modified by:   mark
 * @Last Modified time: 2015-01-13 15:07:06
 */

require 'vendor/autoload.php';

Dotenv::load(__DIR__); // load .env config file

$app = new \Slim\Slim([
	'mode' => (getenv('debug') ? 'development' : 'production'),
	'debug' => getenv('debug'),
	'cookies.secret_key' => md5(getenv('secret')),
	'cookies.httponly' => true,
	'cookies.encrypt' => true,
]);

$app->setName('rdota2.com');

$app->get('/', function () {
	(new Bot())->updateSidebar();
	print 'test';
});

$app->get('/m/:id', function($id) use ($app) {
	$cache = new Redis();
	$cache->connect('127.0.0.1');

	$url = $cache->get($id);
	$app->redirect($url);
});

$app->run();

<?php

session_start();

require_once("vendor/autoload.php");
use Symfony\Component\Dotenv\Dotenv;
use Abraham\TwitterOAuth\TwitterOAuth;


if ($_ENV['IS_DEVELOPMENT']){
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__.'/.env');
}

try{
	
	$msg = "This is my direct message"; 

	$connection = new TwitterOAuth($_ENV['TWITTER_API_KEY'], $_ENV['TWITTER_SECRET_KEY'], $_ENV['TWITTER_ACCESS_TOKEN'], $_ENV['TWITTER_ACCESS_SECRET_TOKEN']);
	$data = [
    'event' => [
        'type' => 'message_create',
        'message_create' => [
            'target' => [
                'recipient_id' => '1411052496'
            ],
            'message_data' => [
                'text' => 'Hello World!'
            ]
        ]
    ]
];
$result = $connection->post('direct_messages/events/new', $data, true);
dd($result);
}catch(\Exception $e){
	dd($e->getMessage());
}

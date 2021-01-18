<?php

require 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

if (isset($_ENV['IS_DEVELOPMENT'])){
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__.'/.env');
}

try {

	$api = new Binance\API($_ENV['API_KEY'],$_ENV['API_SECRET_KEY']);

	$api->useServerTime();

	$percentage = 8;

	$ticker = $api->prices();
	$balances = $api->balances($ticker);
	$btcBalance = $balances['BTC']['btcTotal'];

	$orders = [];
	$symbols = [];
	$counter = 0;
	foreach ($ticker as $key => $value) {
		if ((strpos($key, 'BTC') > -1) && $value >= 0.00000450 && $value <= 0.00002700) {
			$changeStatus = $api->prevDay($key);
			$priceChangePercent = intval($changeStatus['priceChangePercent']);
			$counter++;
			echo $counter . "-) " . $key . " - %" . $priceChangePercent . PHP_EOL;
			if ($priceChangePercent < 10){
				//getting %10 of the float number
				$orders[$key] =  number_format($value - (($percentage / 100) * $value), 8);
				$symbols[] = $key;
			}
		}
	}

	#asort($orders);
	$symbols = array_keys($orders);
	echo "Total orders: " . count($orders) . PHP_EOL;

	shuffle($symbols);

	$scopeVariables = array(
		'api' => $api,
		'btcBalance' => $btcBalance,
		'orders' => $orders
	);

	echo "started listening..." . PHP_EOL;
	$api->trades($symbols, function($api, $symbol, $trades) use ($scopeVariables) {
		if (isset($scopeVariables['orders'][$symbol])){
			if ($trades['price'] <= $scopeVariables['orders'][$symbol]){
				$quantity = intval($scopeVariables['btcBalance'] / $trades['price']);
				#$price = floatval($trades['price']) - 0.00000010;
				#$price = number_format($price, 8);
				$data = array(
					'quantity' => $quantity,
					'symbol' => $symbol
				);

				$orderPrice = $scopeVariables['orders'][$symbol] + 0.00000004;
				$orderPrice = number_format($orderPrice, 8);
				$order = $scopeVariables['api']->buy($symbol, $quantity, $orderPrice);
				$isSentMessage = sendMessage($data);
				print($isSentMessage);
				print_r($order);
				die;
			}
		}
	});
} catch(\Exception $e){
	print_r($e->getMessage()); die;
}



function sendMessage($data){
	try{
	
    $message = "[BUY] [SYMBOL:" . $data['symbol'] . "] - [QUANTITY:" . $data['quantity'] . "]";

	$connection = new TwitterOAuth($_ENV['TWITTER_API_KEY'], $_ENV['TWITTER_SECRET_KEY'], $_ENV['TWITTER_ACCESS_TOKEN'], $_ENV['TWITTER_ACCESS_SECRET_TOKEN']);
	$data = [
	    'event' => [
	        'type' => 'message_create',
	        'message_create' => [
	            'target' => [
	                'recipient_id' => '1411052496'
	            ],
	            'message_data' => [
	                'text' => $message
	            ]
	        ]
	    ]
	];
	$result = $connection->post('direct_messages/events/new', $data, true);
	echo "### Message is sent! ###" . PHP_EOL;
	return true;

	}catch(\Exception $e){
		dd($e->getMessage());
	}

}

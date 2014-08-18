<?php

$cookie_exists = false;
if( isset($_COOKIE['woocommerce_cart_hash'])){
	if(!isset($_GET['key']) && !empty($_COOKIE['woocommerce_order_id']) && !empty($_COOKIE['woocommerce_order_key'])){

		$order_id = $_COOKIE['woocommerce_order_id'];
		$order_key = $_COOKIE['woocommerce_order_key'];
		$current_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

		header('Location: ' . $current_url . $order_id . '/?' . urldecode($order_key) );
		$cookie_exists = true;
	}
}else{
	echo 'Ha ocurrido un error al volver de VisaNet.';
}

?>
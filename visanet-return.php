<?php

error_reporting(E_ALL);

$cookie_exists = false;
if( isset($_COOKIE['woocommerce_cart_hash']) && isset($_COOKIE['woocommerce_order_id']) && isset($_COOKIE['woocommerce_order_key'])){
	if(!isset($_GET['key']) && !empty($_COOKIE['woocommerce_order_id']) && !empty($_COOKIE['woocommerce_order_key'])){

		$order_id = $_COOKIE['woocommerce_order_id'];
		$order_key = $_COOKIE['woocommerce_order_key'];
		$order_returnurl = $_COOKIE['woocommerce_order_returnurl'];
		//$current_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    	echo '<form action="' . $order_returnurl . '" method="post" id="visanet_return_form">
            <input type="hidden" name="IDCOMMERCE" value="' . $_POST['IDCOMMERCE'] . '"/>
            <input type="hidden" name="IDACQUIRER" value="' . $_POST['IDACQUIRER'] . '"/>
            <input type="hidden" name="XMLRES" value="' . $_POST['XMLRES'] . '"/>
            <input type="hidden" name="DIGITALSIGN" value="' . $_POST['DIGITALSIGN'] . '"/>
            <input type="hidden" name="SESSIONKEY" value="' . $_POST['SESSIONKEY'] .'"/>
            <script type="text/javascript">{document.getElementById("visanet_return_form").submit();}</script>
            </form>';

		$cookie_exists = true;
	}
}else{
	echo 'Ha ocurrido un error al volver de VisaNet.';
}

?>
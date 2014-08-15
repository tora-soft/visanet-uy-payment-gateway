<?php
if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}
/*
Plugin Name: WooCommerce VisaNetUY Payment Gateway
Plugin URI: http://www.tora-soft.com
Description: VisaNetUY Payment gateway for woocommerce
Version: 0.1.0
Author: Federico Giust
Author URI: http://www.tora-soft.com
*/
add_action('plugins_loaded', 'woocommerce_visanet_init', 0);

function woocommerce_visanet_init(){

	if(!class_exists('WC_Payment_Gateway')) return;
 
  	class WC_VisaNetUY extends WC_Payment_Gateway{
    
    	public function __construct(){
		
			$this->id 					= 'visanet';
			$this->medthod_title 		= __( 'VisaNet', 'woocommerce' );
			$this->order_button_text 	= __( 'Ir a VisaNet', 'woocommerce' );
			$this->testurl 				= 'https://test2.alignetsac.com/VPOS/MM/transactionStart20.do';
			$this->liveurl 				= 'https://vpayment.verifika.com/VPOS/MM/transactionStart20.do';

			$this->has_fields 			= false;

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title 						= $this->get_option('title');
			$this->description 					= $this->get_option('description');
			$this->idacquirer 					= $this->get_option('idacquirer');
			$this->idcommerce 					= $this->get_option('idcommerce');
			$this->currency_code				= $this->get_option('currency_code');
			$this->vector 						= $this->get_option('vector');
			$this->llaveVPOSCryptoPublica 		= $this->get_option('llaveVPOSCryptoPublica');
			$this->llaveVPOSFirmaPublica 		= $this->get_option('llaveVPOSFirmaPublica');
			$this->llaveComercioCryptoPrivada	= $this->get_option('llaveComercioCryptoPrivada');
			$this->llaveComercioFirmaPrivada	= $this->get_option('llaveComercioFirmaPrivada');
			$this->testmode						= $this->get_option('testmode');
			$this->debug						= $this->get_option('debug');

			// Logs
			if ( 'yes' == $this->debug ) {
				$this->log = new WC_Logger();
			}
 
 			// Actions / Acciones

			
			add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_visanet', array( $this, 'visanet_return_handler' ) );

			// if ( ! $this->is_valid_for_use() ) {
			// 	$this->enabled = false;
			// }

   		}

		/**
		 * Check if this gateway is enabled and if the required fields have been filled. 
		 * Chequear si el gateway esta habilitado y si los campos requeridos han sido completados.
		 * @access public
		 * @return bool
		 */
		function is_valid_for_use() {
			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Chequeando si los campos requeridos han sido llenados y si es valido la configuracion. ' );
			}
			if ( empty($this->idacquirer) || empty($this->idcommerce) || empty($this->vector) || empty($this->llaveVPOSCryptoPublica) || empty($this->llaveVPOSFirmaPublica) || empty($this->llaveComercioCryptoPrivada) || empty($this->llaveComercioFirmaPrivada) ) {
				return false;
			}
			return true;
		}


    	function init_form_fields(){
 
       		$this->form_fields = array(
                'enabled' => array(
                    'title' 	  => __('Habilita/Deshabilita', 'woocommerce'),
                    'type' 		  => 'checkbox',
                    'label' 	  => __('Habilitar VisaNet modulo de pagos.', 'woocommerce'),
                    'default' 	  => 'no'),
                'title' => array(
                    'title'  	  => __('Titulo:', 'woocommerce'),
                    'type'		  => 'text',
                    'description' => __('Nombre del metodo de pago como lo ve el usuario.', 'woocommerce'),
                    'default'     => __('VisaNetUY', 'woocommerce')),
                'description' => array(
                    'title'       => __('Descripción:', 'woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('Esto controla la descripción del metodo de pago que ve el usuario durante el checkout.', 'woocommerce'),
                    'default'     => __('Pague seguro con tarjeta de credito o de debito a travez de VisaNet.', 'woocommerce')),
                'idacquirer' => array(
                    'title'       => __('ID Acquirer', 'woocommerce'),
                    'type' 		  => 'text',
                    'description' => __('Este dato es proporcionado por VisaNet.')),
                'idcommerce' => array(
                    'title' 	  => __('ID Commerce', 'woocommerce'),
                    'type' 		  => 'text',
                    'description' => __('Este dato es proporcionado por VisaNet.')),
                'currency_code' => array(
                    'title' 	  => __('Moneda', 'woocommerce'),
                    'type' 		  => 'text',
                    'description' => __('Codigo ISO alfanumerico de 3 caracteres. Debe estar en las monedas permitidas para su comercio.')),
                'vector' => array(
                    'title' 	  => __('Vector de inicializacion', 'woocommerce'),
                    'type' 		  => 'text',
                    'description' =>  __('Este dato es generado por el comercio, cadena alfanumerica (0 - 9 y a - f) de un máximo de 16 caracteres.', 'woocommerce'),
                ),
                'llaveVPOSCryptoPublica' => array(
                    'title'  	  => __('Llave VPOS Cifrada Pública', 'woocommerce'),
                    'type' 		  => 'textarea',
                    'description' =>  __('Esta llave es proporcionado por VisaNet.', 'woocommerce'),
                ),
                'llaveVPOSFirmaPublica' => array(
                    'title'  	  => __('Llave VPOS Firma Pública', 'woocommerce'),
                    'type' 		  => 'textarea',
                    'description' =>  __('Esta llave es proporcionado por VisaNet.', 'woocommerce'),
                ),
                'llaveComercioCryptoPrivada' => array(
                    'title'  	  => __('Llave Comercio Cifrada Privada', 'woocommerce'),
                    'type' 		  => 'textarea',
                    'description' =>  __('Esta llave es generada por el comercio, como lo indica en la guia provista por VisaNet.', 'woocommerce'),
                ),
                'llaveComercioFirmaPrivada' => array(
                    'title'  	  => __('Llave Comercio Firma Privada', 'woocommerce'),
                    'type' 		  => 'textarea',
                    'description' =>  __('Esta llave es generada por el comercio, como lo indica en la guia provista por VisaNet.', 'woocommerce'),
                ),
	 			'testmode' => array(
					'title'       => __( 'VisaNet Modo de pruebas', 'woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Habilitar modo de pruebas', 'woocommerce' ),
					'default'     => 'no',
					'description' => __( 'Se puede utilizar para probar pagos.', 'woocommerce' )
				),
				'debug' => array(
					'title'       => __( 'Debug Log', 'woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Habilitar logging', 'woocommerce' ),
					'default'     => 'no',
					'description' => sprintf( __( 'Habilitar Log. Se guardan en <code>woocommerce/logs/visanet-%s.txt</code>', 'woocommerce' ), sanitize_file_name( wp_hash( 'visanet' ) ) ),
				)
            );
    	}
 
       	public function admin_options(){
	        echo '<h3>'.__('Modulo de pagos VisaNet UY', 'woocommerce').'</h3>';
	        echo '<p>'.__('').'</p>';
	        echo '<table class="form-table">';
	        // Generate the HTML For the settings form.
	        $this->generate_settings_html();
	        echo '</table>'; 
    	}
 

		/**
		 * Output for the order received page.
		 *
		 * @access public
		 * @return void
		 */
    	function receipt_page( $order ){
			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Mostrando pagina de recibo ');
			}
        	echo '<p>' . __('Gracias por su compra, por favor haga click debajo para pagar en VisaNet.', 'woocommerce').'</p>';
        	echo $this->generate_visanet_form( $order );
    	}


    	function get_array_send($order, $txnid){

			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Generando array_send ' . $order->get_order_number() . ' - ' . $txnid );
			}

	        $array_send['acquirerId'] 				= $this->idacquirer;
	        $array_send['commerceId'] 				= $this->idcommerce;
	        $array_send['purchaseOperationNumber'] 	= $txnid;
	        $array_send['purchaseAmount'] 			= $order->order_total * 100;
	        $array_send['purchaseCurrencyCode'] 	= $this->currency_code;
	        
			$array_send['billingAddress']			= $order->billing_address_1; 
			$array_send['billingCity']				= $order->billing_city; 
			$array_send['billingState']				= $order->billing_state; 
			$array_send['billingCountry']			= $order->billing_country; 
			$array_send['billingZIP']				= $order->billing_zip; 
			$array_send['billingPhone']				= $order->billing_phone; 
			$array_send['billingEMail']				= $order->billing_email; 
			$array_send['billingFirstName']			= $order->billing_first_name; 
			$array_send['billingLastName']			= $order->billing_last_name; 
			$array_send['language']					= 'SP'; //En español

			$array_send = apply_filters( 'woocommerce_visanet_array_send', $array_send );

			return $array_send;

    	}

    	function get_array_get( $order ){

			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Generando array_get ' . $order->get_order_number() );
			}

			$array_get['XMLREQ']="";
			$array_get['DIGITALSIGN']="";
			$array_get['SESSIONKEY']=""; 

			$array_get = apply_filters( 'woocommerce_visanet_array_get', $array_get );

			return $array_get;

    	}


	    /**
	     * Generate visanet button link
	     **/
	    public function generate_visanet_form($order_id){
 
			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Generando formulario de orden para orden ' . $order_id );
			}

	        $order = new WC_Order($order_id);

			if ( 'yes' == $this->testmode ) {
				$visanet_adr = $this->testurl;
			} else {
				$visanet_adr = $this->liveurl;
			}

	        $txnid = 'LS' . $order_id . date("ymd");
	  
	 		$array_send = $this->get_array_send( $order, $txnid );

	 		$array_get 	= $this->get_array_get( $order ); 

 			$this->VPOSSend( $array_send, $array_get, $this->llaveVPOSCryptoPublica, $this->llaveComercioFirmaPrivada, substr($this->vector, 0, 16) );

			wc_enqueue_js( '
				$.blockUI({
						message: "' . esc_js( __( 'Gracias por su compra. Ahora lo vamos a redireccionar a VisaNet donde ud puede realizar el pago de manera segura.', 'woocommerce' ) ) . '",
						baseZ: 99999,
						overlayCSS:
						{
							background: "#fff",
							opacity: 0.6
						},
						css: {
							padding:        "20px",
							zindex:         "9999999",
							textAlign:      "center",
							color:          "#555",
							border:         "3px solid #aaa",
							backgroundColor:"#fff",
							cursor:         "wait",
							lineHeight:		"24px",
						}
					});
				jQuery("#submit_visanet_payment_form").click();
			');

			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Enviando formulario de orden para orden ' . $order_id . ' - ' . $txnid );
			}

        	return '<form action="' . $visanet_adr . '" method="post" id="visanet_payment_form">
	            <input type="hidden" name="IDACQUIRER" value="' . $this->idacquirer . '"/>
	            <input type="hidden" name="IDCOMMERCE" value="' . $this->idcommerce . '"/>
	            <input type="hidden" name="XMLREQ" value="' . $array_get['XMLREQ'] . '"/>
	            <input type="hidden" name="DIGITALSIGN" value="' . $array_get['DIGITALSIGN'] . '"/>
	            <input type="hidden" name="SESSIONKEY" value="' . $array_get['SESSIONKEY'] .'"/>
	            <input type="submit" class="button-alt" id="submit_visanet_payment_form" value="'.__('Pagar a travez de VisaNet', 'woocommerce').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancelar orden &amp; restaurar carro', 'woocommerce').'</a>
	            </form>';
	    }

	    /**
	     * Process the payment and return the result
	     **/
	    function process_payment($order_id){
			
			$order = new WC_Order( $order_id );

			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Procesando pago ' . $order_id . ' - return url ' . $order->get_checkout_payment_url( true ));
			}

			return array(
				'result' 	=> 'success',
				'redirect'	=> $order->get_checkout_payment_url( true )
			);

	    }
 
	    /**
	     * Check for valid visanet server callback
	     **/
	    function check_visanet_response(){
			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Checking VisaNetUY response is valid.' );
			}
	 
	    }
 
	    function visanet_return_handler(){

			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Procesando la vuelta de VisaNet ');
			}

			$arrayIn['IDCOMMERCE'] = $_POST['IDCOMMERCE'];
			$arrayIn['IDACQUIRER'] = $_POST['IDACQUIRER'];
			$arrayIn['XMLRES'] = $_POST['XMLRES'];
			$arrayIn['DIGITALSIGN'] = $_POST['DIGITALSIGN'];
			$arrayIn['SESSIONKEY'] = $_POST['SESSIONKEY'];

			$arrayOut = array();

			if( $this->VPOSResponse($arrayIn,$arrayOut, $this->llaveVPOSFirmaPublica, $this->llaveComercioCryptoPrivada, $this->vector) ){
				//La salida esta en $arrayOut con todos los parámetros decifrados devueltos por el VPOS 
				$arrayOut['authorizationResult']= $resultadoAutorizacion; 
				$arrayOut['authorizationCode']= $codigoAutorizacion;

				if ( 'yes' == $this->debug ) {
					$this->log->add( 'visanet', 'Error: Transaccion rechazada. arrayOut ' . var_dump($arrayOut) );
				}
				

				if ( $resultadoAutorizacion != '00' || $resultadoAutorizacion != '11') {

					if ( 'yes' == $this->debug ) {
						$this->log->add( 'visanet', 'Error: Transaccion rechazada.' );
					}

					// Put this order on-hold for manual checking
					//$order->update_status( 'on-hold',  __( 'Error: Transaccion rechazada.', 'woocommerce' ) );
					return true;

				} else {
					if ( 'yes' == $this->debug ) {
						$this->log->add( 'visanet', 'Pago completo.' );
					}
					// Store PP Details
					//update_post_meta( $order->id, 'Transaction ID', wc_clean( $posted['tx'] ) );

					//$order->add_order_note( __( 'Pago completo', 'woocommerce' ) );
					//$order->payment_complete();
					return true;
				}



			}else{
				//Puede haber un problema de mala configuración de las llaves, vector de
				//inicializacion o el VPOS no ha enviado valores correctos 
				return false;
			}

	    }

	    function showMessage($content){
			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Mostrando mensaje:  ' . $content );
			}
            return '<div class="box '.$this->msg['class'].'-box">'.$this->msg['message'].'</div>'.$content;
        }
    
	    // get all pages
	    function get_pages($title = false, $indent = true) {

	        $wp_pages = get_pages('sort_column=menu_order');
	        $page_list = array();
	        if ($title) $page_list[] = $title;
	        foreach ($wp_pages as $page) {
	            $prefix = '';
	            // show indented child pages?
	            if ($indent) {
	                $has_parent = $page->post_parent;
	                while($has_parent) {
	                    $prefix .=  ' - ';
	                    $next_page = get_page($has_parent);
	                    $has_parent = $next_page->post_parent;
	                }
	            }
	            // add to page list array array
	            $page_list[$page->ID] = $prefix . $page->post_title;
	        }
	        return $page_list;
	    }

		function createXMLPHP5($arreglo){

			$camposValidos_envio = array(
				'acquirerId',
				'commerceId',
				'purchaseCurrencyCode',
				'purchaseAmount',
				'purchaseOperationNumber',
				'billingAddress',
				'billingCity',
				'billingState',
				'billingCountry',
				'billingZIP',
				'billingPhone',
				'billingEMail',
				'billingFirstName',
				'billingLastName',
				'language',
				'commerceMallId',
				'terminalCode',
				'tipAmount',
				'HTTPSessionId',
				'shippingAddress',
				'shippingCity',
				'shippingState',
				'shippingCountry',
				'shippingZIP',
				'shippingPhone',
				'shippingEMail',
				'shippingFirstName',
				'shippingLastName',
				'reserved1',
				'reserved2',
				'reserved3',
				'reserved4',
				'reserved5',
				'reserved6',
				'reserved7',
				'reserved8',
				'reserved9',
				'reserved10',
				'reserved11',
				'reserved12',
				'reserved13',
				'reserved14',
				'reserved15',
				'reserved16',
				'reserved17',
				'reserved18',
				'reserved19',
				'reserved20',
				'reserved21',
				'reserved22',
				'reserved23',
				'reserved24',
				'reserved25',
				'reserved26',
				'reserved27',
				'reserved28',
				'reserved29',
				'reserved30',
				'reserved31',
				'reserved32',
				'reserved33',
				'reserved34',
				'reserved35',
				'reserved36',
				'reserved37',
				'reserved38',
				'reserved39',
				'reserved40'
			);

			$arrayTemp = array();
			$taxesName = array();
			$taxesAmount = array();

			$dom = new DOMDocument('1.0', 'iso-8859-1');

			$raiz = $dom->createElement('VPOSTransaction1.2');

			$dom->appendChild($raiz);

			foreach($arreglo as $key => $value){

				if(in_array($key,$camposValidos_envio)){
					$arrayTemp[$key] = $value;
				}
				else if(preg_match('tax_([0-9]{1}|[0-9]{2})_name',$key)){
					$keyam = preg_replace('(^tax_)|(_name$)','',$key);
					$taxesName[$keyam] = $value;
					//array_push($taxesName,array($keyam => $value));
				}else if(preg_match('tax_([0-9]{1}|[0-9]{2})_amount',$key)){
					$keyam = preg_replace('(^tax_)|(_amount$)','',$key);
					$taxesAmount[$keyam] = $value;
					//array_push($taxesAmount,array($keyam => $value));
				}else{
					die($key.' is not allowed in plugin');
				}
			}

			foreach($arrayTemp as $key => $value){
				$elem = new DOMElement($key,$value);
				$raiz->appendChild($elem);
			}

			if(count($taxesName)>0){
				$elem = $raiz->appendChild(new DOMElement('taxes'));
				foreach($taxesName as $key => $value){
					$tax = $elem->appendChild(new DOMElement('Tax'));
					$tax->setAttributeNode(new DOMAttr('name',$value));
					$tax->setAttributeNode(new DOMAttr('amount',$taxesAmount[$key]));
				}
			}
			return $dom->saveXML();
		}

		function VPOSSend($arrayIn,&$arrayOut,$llavePublicaCifrado,$llavePrivadaFirma,$VI){

			$veractual = phpversion();

			if(version_compare($veractual,"5.0")<0){
				die('PHP version is '.$veractual.'and should be >=5.0');
			}

			$xmlSalida = $this->createXMLPHP5($arrayIn);

			//Genera la firma Digital
			$firmaDigital = $this->BASE64URL_digital_generate($xmlSalida,$llavePrivadaFirma);

			//Ya se genero el XML y se genera la llave de sesion
			$llavesesion = $this->generateSessionKey();

			//Se cifra el XML con la llave generada
			$xmlCifrado = $this->BASE64URL_symmetric_cipher($xmlSalida,$llavesesion,$VI);

			if(!$xmlCifrado) return null;

			//Se cifra la llave de sesion con la llave publica dada
			$llaveSesionCifrada = $this->BASE64URLRSA_encrypt($llavesesion,$llavePublicaCifrado);

			if(!$llaveSesionCifrada) return null;

			if(!$firmaDigital) return null;

			$arrayOut['SESSIONKEY'] = $llaveSesionCifrada;
			$arrayOut['XMLREQ'] = $xmlCifrado;
			$arrayOut['DIGITALSIGN'] = $firmaDigital;

			return true;
		}

	 	function VPOSResponse($arrayIn,&$arrayOut,$llavePublicaFirma,$llavePrivadaCifrado,$VI){

	 		$veractual = phpversion();

			if(version_compare($veractual,"5.0")<0){

				trigger_error('La version de PHP es menor a la 5.0', E_USER_ERROR);
				return false;
			}

	 		if($arrayIn['SESSIONKEY']==null
				|| $arrayIn['XMLRES']==null
				|| $arrayIn['DIGITALSIGN'] == null){
					return false;
			}

			$llavesesion = $this->BASE64URLRSA_decrypt($arrayIn['SESSIONKEY'],$llavePrivadaCifrado);

			$xmlDecifrado = $this->BASE64URL_symmetric_decipher($arrayIn['XMLRES'],$llavesesion,$VI);

			$validation = $this->BASE64URL_digital_verify($xmlDecifrado,$arrayIn['DIGITALSIGN'],$llavePublicaFirma);

			if($validation){

				$arrayOut = $this->parseXMLPHP5($xmlDecifrado);

				return true;
			}
			else{

				return false;
			}

	 	}

		function is_num($s) {
			for ($i=0; $i<strlen($s); $i++) {
				if (($s[$i]<'0') or ($s[$i]>'9')) {return false;}
			}
			return true;
		}

	 	function generateSessionKey(){
	 		srand((double)microtime()*1000000);
	 		return mcrypt_create_iv(16,MCRYPT_RAND);
	 	}

		function BASE64URLRSA_encrypt ($valor,$publickey) {
	 		if (!($pubres = openssl_pkey_get_public($publickey))){
	 			die("Public key is not valid");
	 		}

			$salida = "";

			$resp = openssl_public_encrypt($valor,$salida,$pubres,OPENSSL_PKCS1_PADDING);

			openssl_free_key($pubres);

			if($resp){
				$base64 = base64_encode($salida);
				$base64 = preg_replace('(/)','_',$base64);
				$base64 = preg_replace('(\+)','-',$base64);
				$base64 = preg_replace('(=)','.',$base64);
				return $base64;
			}else{
				die('RSA Ciphering could not be executed');
			}
		}

		function BASE64URLRSA_decrypt($valor,$privatekey){

			if (!($privres = openssl_pkey_get_private(array($privatekey,null))))
			{
			 	die('Invalid private RSA key has been given');
			}

			$salida = "";

			$pas = preg_replace('(_)','/',$valor);
			$pas = preg_replace('(-)','+',$pas);
			$pas = preg_replace('(\.)','=',$pas);

			$temp = base64_decode($pas);

			$resp = openssl_private_decrypt($temp,$salida,$privres,OPENSSL_PKCS1_PADDING);

			openssl_free_key($privres);

			if($resp){
				return $salida;
			}else{
				die('RSA deciphering was not succesful');
			}
		}

		function BASE64URL_symmetric_cipher($dato, $key, $vector){

			$tamVI = strlen($vector);

			if($tamVI != 16){
				trigger_error('Initialization Vector must have 16 hexadecimal characters', E_USER_ERROR);
				return null;
			}

			if(strlen($key) != 16){
				trigger_error("Simetric Key doesn't have length of 16", E_USER_ERROR);
				return null;
			}

			$binvi = pack("H*", $vector);

			if($binvi == null){
				trigger_error("Initialization Vector is not valid, must contain only hexadecimal characters", E_USER_ERROR);
				return null;
			}

			$key .= substr($key,0,8); // agrega los primeros 8 bytes al final

			$text = $dato;
		  	$block = mcrypt_get_block_size('tripledes', 'cbc');
		   	$len = strlen($text);
		   	$padding = $block - ($len % $block);
		   	$text .= str_repeat(chr($padding),$padding);

			$crypttext = mcrypt_encrypt(MCRYPT_3DES, $key, $text, MCRYPT_MODE_CBC, $binvi);

			$crypttext = base64_encode($crypttext);
			$crypttext = preg_replace('(/)','_',$crypttext);
			$crypttext = preg_replace('(\+)','-',$crypttext);
			$crypttext = preg_replace('(=)','.',$crypttext);

			return $crypttext;
		}

		//-------------------------------------------------------------------------------------
		// Esta funcion se encarga de desencriptar los datos recibidos del MPI
		// Recibe como parametro el dato a desencriptar
		//-------------------------------------------------------------------------------------
		function BASE64URL_symmetric_decipher($dato, $key, $vector){
			$tamVI = strlen($vector);

			if($tamVI != 16){
				trigger_error("Initialization Vector must have 16 hexadecimal characters", E_USER_ERROR);
				return null;
			}
			if(strlen($key) != 16){
				trigger_error("Simetric Key doesn't have length of 16", E_USER_ERROR);
				return null;
			}

			$binvi = pack("H*", $vector);

			if($binvi == null){
				trigger_error("Initialization Vector is not valid, must contain only hexadecimal characters", E_USER_ERROR);
				return null;
			}

			$key .= substr($key,0,8); // agrega los primeros 8 bytes al final

			$pas = preg_replace('(_)','/',$dato);
			$pas = preg_replace('(-)','+',$pas);
			$pas = preg_replace('(\.)','=',$pas);

			$crypttext = base64_decode($pas);

			$crypttext2 = mcrypt_decrypt(MCRYPT_3DES, $key, $crypttext, MCRYPT_MODE_CBC, $binvi);

			$block = mcrypt_get_block_size('tripledes', 'cbc');
			$packing = ord($crypttext2{strlen($crypttext2) - 1});
			if($packing and ($packing < $block)){
				for($P = strlen($crypttext2) - 1; $P >= strlen($crypttext2) - $packing; $P--){
					if(ord($crypttext2{$P}) != $packing){
						$packing = 0;
					}
				}
			}

			$crypttext2 = substr($crypttext2,0,strlen($crypttext2) - $packing);

			return $crypttext2;
		}

		//-------------------------------------------------------------------------------------
		// Esta funcion se encarga de generar una firma digital de $dato usando
		// la llave privada en $privatekey
		//-------------------------------------------------------------------------------------

	 	function BASE64URL_digital_generate($dato, $privatekey){

	 		$privres = openssl_pkey_get_private(array($privatekey,null));
	 		 if (!$privres){
			 	die("Private key is not valid");
			 }
	 		$firma = "";

	 		$resp = openssl_sign($dato,$firma,$privres);

	 		openssl_free_key($privres);

			if($resp){

				$base64 = base64_encode($firma);

				$crypttext = preg_replace('(/)' ,'_',$base64);
				$crypttext = preg_replace('(\+)','-',$crypttext);
				$crypttext = preg_replace('(=)' ,'.',$crypttext);

				//$urlencoded = urlencode($base64);
				return $crypttext;
			}else{
				die("RSA Signature was unsuccesful");
			}


	 	}

	 	function BASE64URL_digital_verify($dato,$firma, $publickey){

	 		if (!($pubres = openssl_pkey_get_public($publickey))){
	 			die("Public key is not valid");
	 		}

	 		$pas = preg_replace('(_)','/',$firma);
			$pas = preg_replace('(-)','+',$pas);
			$pas = preg_replace('(\.)','=',$pas);

	 		$temp = base64_decode($pas);

	 		$resp = openssl_verify($dato,$temp,$pubres);

	 		openssl_free_key($pubres);

	 		return $resp;
	 	}

		function parseXMLPHP5($xml){

			$arregloSalida = array();

			$dom = new DOMDocument();
			$dom->loadXML($xml);

			$raiz = $dom->getElementsByTagName('VPOSTransaction1.2')->item(0);

			$nodoHijo = null;
			if($raiz->hasChildNodes()){
				$nodoHijo = $raiz->firstChild;
				$arregloSalida[$nodoHijo->nodeName] = $nodoHijo->nodeValue;
			}

			while (($nodoHijo=$nodoHijo->nextSibling)!=null){
				$i = 1;
				if(strcmp($nodoHijo->nodeName,'taxes')==0){
					if($nodoHijo->hasChildNodes()){
						$nodoTax = $nodoHijo->firstChild;
						$arregloSalida['tax_'.$i.'_name'] = $nodoTax->getAttribute('name');
						$arregloSalida['tax_'.$i.'_amount'] = $nodoTax->getAttribute('amount');
						$i++;
					}

					while (($nodoTax=$nodoTax->nextSibling)!=null) {
						$arregloSalida['tax_'.$i.'_name'] = $nodoTax->getAttribute('name');
						$arregloSalida['tax_'.$i.'_amount'] = $nodoTax->getAttribute('amount');
						$i++;
					}

				}else{
					$arregloSalida[$nodoHijo->nodeName] = $nodoHijo->nodeValue;
				}
			}
			return $arregloSalida;
		}


	}
   	
   	/**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_visanet_gateway($methods) {
        $methods[] = 'WC_VisaNetUY';
        return $methods;
    }
 
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_visanet_gateway' );

}
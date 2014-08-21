<?php

/*
Plugin Name: WooCommerce VisaNetUY Payment Gateway
Plugin URI: http://www.tora-soft.com
Description: VisaNetUY Payment gateway for woocommerce
Version: 1.0.1
Author: Federico Giust
Author URI: http://www.tora-soft.com
*/
add_action('plugins_loaded', 'woocommerce_visanet_init', 0);

function woocommerce_visanet_init(){

	if(!class_exists('WC_Payment_Gateway')) return;
 
  	class WC_VisaNetUY extends WC_Payment_Gateway{
    
    	public function __construct(){
		
			$this->id 					= 'visanet';
			$this->method_title 		= __( 'VisaNet', 'woocommerce' );
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

			// Logs - If enabled it will save a txt file in woocommerce/logs/
			if ( 'yes' == $this->debug ) {
				$this->log = new WC_Logger();
			}
 
 			// Actions

 			// Receipt page before redirecting to payment gateway
			add_action( 'woocommerce_receipt_' . $this->id 							, array( $this, 'receipt_page'   ));
			// Admin options - Generate the admin form 
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id 	, array( $this, 'process_admin_options' ));
            add_action( 'woocommerce_thankyou_' . $this->id							, array( $this, 'check_response'  ));

			if ( ! $this->is_valid_for_use() ) {
				$this->enabled = false;
			}

   		}

		/**
		 * Check if this gateway is enabled and if the required fields have been filled. 
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

		/**
		* Initialize admin form fields for the user to configure the plugin.
		* @access public
		* @return void
		*/
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
                    'default'     => __('VisaNet', 'woocommerce')),
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
 
		/**
		 * Output for the admin options page.
		 *
		 * @access public
		 * @return void
		 */ 	
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
		 * @param object $order
		 * @return void
		 */
    	function receipt_page( $order ){
			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Mostrando pagina de recibo y generando formulario POST. ');
			}
        	echo '<p>' . __('Gracias por su compra, por favor haga click debajo para pagar en VisaNet.', 'woocommerce').'</p>';
        	echo $this->generate_visanet_form( $order );
    	}

		/**
		 * Generate array_send with arguments to be passed on later to VPOSSend.
		 *
		 * @access public
		 * @param object $order
		 * @param string $txnid
		 * @return $array_send
		 */

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

		/**
		 * Generate array_get empty to be passed on later to VPOSSend.
		 *
		 * @access public
		 * @param object $order
		 * @return $array_get
		 */

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
	     * Generate visanet POST form
	     * 
	     * @access public
	     * @param string $order_id
	     * @return form and make post to redirect to VisaNet
	     **/
	    public function generate_visanet_form($order_id){
 
			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Generando formulario de orden para orden ' . $order_id . ' - ' . time());
			}

	        $order = new WC_Order($order_id);

			if ( 'yes' == $this->testmode ) {
				$visanet_adr = $this->testurl;
			} else {
				$visanet_adr = $this->liveurl;
			}

			$event_time = time();
			$event_length = 5;
			 
			$timestamp = strtotime("$event_time");
			$etime = strtotime("+$event_length minutes", $timestamp);
			$expire = date('H:i:s', $etime);

			$return_url = parse_url($order->get_checkout_order_received_url());

			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Guardando cookie ' . $order_id . ' - woocommerce_order_id: '.$order_id.'|woocommerce_order_key:'.$return_url['query']  );
			}

			if(!empty($_COOKIE['woocommerce_order_id'])) unset($_COOKIE['woocommerce_order_id']);
			if(!empty($_COOKIE['woocommerce_order_key'])) unset($_COOKIE['woocommerce_order_key']);
			if(!empty($_COOKIE['woocommerce_order_returnurl'])) unset($_COOKIE['woocommerce_order_returnurl']);

			setcookie("woocommerce_order_id", $order_id, time()+3600, "/", $_SERVER['SERVER_NAME']);
			setcookie("woocommerce_order_key", $return_url['query'], time()+3600, "/", $_SERVER['SERVER_NAME']);
			setcookie("woocommerce_order_returnurl", $order->get_checkout_order_received_url(), time()+3600, "/", $_SERVER['SERVER_NAME']);

			$suffix_order_id = uniqid(rand(10,1000),false);
			$suffix_order_id = substr($suffix_order_id,rand(0,strlen($suffix_order_id) - 6),6);

	        //$txnid = 'LS' . $order_id . date("ymd");
	        $txnid = $order_id . $suffix_order_id;
	  
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
	     * Process the payment 
	     * 
	     * @access public
	     * @param string $order_id
	     * @return array
	     **/
	    function process_payment($order_id){
			
			$order = new WC_Order( $order_id );

			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Procesando orden: ' . $order_id . ' - ' . time() . ' | checkout url ' . $order->get_checkout_payment_url( true ) . ' | return url ' . $order->get_checkout_order_received_url());
			}

			return array(
				'result' 	=> 'success',
				'redirect'	=> $order->get_checkout_payment_url( true )
			);

	    }
 
	    /**
	     * Check for visanet response
	     * 
	     * @access public
	     * @return transaction result and thank you page
	     **/
 
	    function check_response(){
	    	global $woocommerce;

			if( isset($_POST['order_id']) ){

				if ( 'yes' == $this->debug ) {
					$this->log->add( 'visanet', 'Procesando la vuelta de VisaNet orden: ' . $_POST['order_id'] );
				}

				$order = new WC_Order( $_POST['order_id'] );

				$arrayIn = array(
					'IDCOMMERCE' => $_POST['IDCOMMERCE'],
					'IDACQUIRER' => $_POST['IDACQUIRER'],
					'XMLRES'	 => $_POST['XMLRES'],
					'DIGITALSIGN'=> $_POST['DIGITALSIGN'],
					'SESSIONKEY' => $_POST['SESSIONKEY']
					);

				$arrayOut = array();

				if( $this->VPOSResponse( $arrayIn, $arrayOut, $this->llaveVPOSFirmaPublica, $this->llaveComercioCryptoPrivada, $this->vector) ){
					//La salida esta en $arrayOut con todos los parametros decifrados devueltos por el VPOS 
					if(isset($arrayOut['authorizationResult'])){
						$resultadoAutorizacion = $arrayOut['authorizationResult'];
					}
					if(isset($arrayOut['authorizationCode'])){
						$codigoAutorizacion    = $arrayOut['authorizationCode'];
					}
					

					if ( 'yes' == $this->debug ) {
						$this->log->add( 'visanet', 'Resultado de la transaccion: ' . $resultadoAutorizacion  . ' | DATA : ' . json_encode($arrayOut));
					}

					if ( $resultadoAutorizacion != '00' && $resultadoAutorizacion != '11') {

						if ( 'yes' == $this->debug ) {
							$this->log->add( 'visanet', 'Error: ' . $resultadoAutorizacion . ' | ' . $arrayOut['errorCode'] . ' - ' . $arrayOut['errorMessage'] );
						}
						$order->update_status( 'failed',  __( 'Error: ' . $resultadoAutorizacion . ' | ' . $arrayOut['errorCode'] . ' - ' . $arrayOut['errorMessage'], 'woocommerce' ) );

						$result = 'failed';

						$this->web_redirect($order->get_checkout_order_received_url());
			
					} else {

						if ( 'yes' == $this->debug ) {
							$this->log->add( 'visanet', 'Pago Completado: ' . $resultadoAutorizacion   );
						}

						// Reduce stock levels
						$order->reduce_order_stock();

						// Remove cart
						$woocommerce->cart->empty_cart();

						$order->update_status( 'completed',  __( 'Completa : ' . $resultadoAutorizacion . ' | ' . $arrayOut['errorCode'] . ' - ' . $arrayOut['errorMessage'], 'woocommerce' ) );

						// Store PP Details
						update_post_meta( $order->id, 'Transaccion : ', wc_clean( $arrayOut['purchaseOperationNumber'] ) );

						$order->payment_complete();
						$result = 'success';

					}



				}else{
					//Puede haber un problema de mala configuración de las llaves, vector de
					//inicializacion o el VPOS no ha enviado valores correctos 
					if ( 'yes' == $this->debug ) {
						$this->log->add( 'visanet', 'Ha ocurrido un error, tal vez las llaves o vector esten mal configurados.' );
					}
					$result = 'failed';
					
					$order->update_status( 'failed',  __( 'Error! Hubo algun problema en la comunicación con VisaNet. Verifique que la configuración esta correcta.', 'woocommerce' ) );

					$this->web_redirect($order->get_checkout_order_received_url());

				}

			}

	    }

	    /**
	     * Make a redirect when needed 
	     * 
	     * @access public
	     * @param string $url
	     * @return redirect
	     */
		public function web_redirect($url){

			echo "<html><head><script language=\"javascript\">
				<!--
				window.location=\"{$url}\";
				//-->
				</script>
				</head><body><noscript><meta http-equiv=\"refresh\" content=\"0;url={$url}\"></noscript></body></html>";

		}

		public function thankyou_page($order_id) {

		}

		/**
		 * VPOS Plugin provided by VisaNet 
		 * Converted into class by Federico Giust
		 **/

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

			while (($nodoHijo=$nodoHijo->nextSibling)!=null) {
				$i = 1;

					$arregloSalida[$nodoHijo->nodeName] = $nodoHijo->nodeValue;
				


			}

			return $arregloSalida;
		}

		// Ends VPOS Plugin provided by VisaNet

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
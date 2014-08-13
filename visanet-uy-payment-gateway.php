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
add_action('plugins_loaded', 'woocommerce_visanetuy_init', 0);

function woocommerce_visanetuy_init(){

	if(!class_exists('WC_Payment_Gateway')) return;
 
	include("vpos_plugin.php");

  	class WC_VisaNetUY extends WC_Payment_Gateway{
    
    	public function __construct(){
		
			$this->id 					= 'VisaNetUY';
			$this->medthod_title 		= 'VisaNet UY';
			$this->medthod_description 	= 'VisaNet UY';
			$this->has_fields 			= false;

			$this->init_form_fields();
			$this->init_settings();

			$this->title 				= $this->get_option('title');
			$this->description 			= $this->get_option('description');
			$this->idacquirer 			= $this->get_option('idacquirer');
			$this->idcommerce 			= $this->get_option('idcommerce');
			$this->vector 				= $this->get_option('vector');
			$this->llavePublica 		= $this->get_option('llavePublica');
			$this->llavePrivada 		= $this->get_option('llavePrivada');
			$this->redirect_page_id 	= $this->get_option('redirect_page_id');
			$this->testurl 				= 'https://servicios.alignet.com/VPOS/MM/transactionStart20.do';
			$this->liveurl 				= 'https://vpayment.verifika.com/VPOS/MM/transactionStart20.do';
			$this->testmode				= $this->get_option( 'testmode' );
			$this->debug				= $this->get_option( 'debug' );

			// Logs
			if ( 'yes' == $this->debug ) {
				$this->log = new WC_Logger();
			}
 
 			// Actions / Acciones
			
			add_action( 'woocommerce_receipt_visanetuy', array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_visanet', array( $this, 'visanet_return_handler' ) );

			if ( ! $this->is_valid_for_use() ) {
				$this->enabled = false;
			}

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
			if ( empty($this->idacquirer) || empty($this->idcommerce) || empty($this->vector) || empty($this->llavePublica) || empty($this->llavePrivada) ) {
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
                'vector' => array(
                    'title' 	  => __('Vector de inicializacion', 'woocommerce'),
                    'type' 		  => 'text',
                    'description' =>  __('Este dato es generado por el comercio, cadena alfanumerica (0 - 9 y a - f) de un máximo de 16 caracteres.', 'woocommerce'),
                ),
                'llavePublica' => array(
                    'title'  	  => __('Llave publica', 'woocommerce'),
                    'type' 		  => 'textarea',
                    'description' =>  __('Esta llave es proporcionado por VisaNet.', 'woocommerce'),
                ),
                'llavePrivada' => array(
                    'title'  	  => __('Llave privada', 'woocommerce'),
                    'type' 		  => 'textarea',
                    'description' =>  __('Esta llave es generada por el comercio, como lo indica en la guia provista por VisaNet.', 'woocommerce'),
                ),
                'redirect_page_id' => array(
                    'title' 	  => __('Pagina de retorno'),
                    'type'  	  => 'select',
                    'options' 	  => $this->get_pages('Por favor elija'),
                    'description' => "Pagina a mostrar luego de pago existoso."
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
				$this->log->add( 'visanet', 'Mostrando pagina de recibo ' . $order->get_order_number() );
			}
        	echo '<p>' . __('Thank you for your order, please click the button below to pay with VisaNet UY.', 'woocommerce').'</p>';
        	echo $this->generate_visanetuy_form( $order );
    	}


    	function get_array_send($order, $txnid){

			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Generando array_send ' . $order->get_order_number() . ' - ' . $txnid );
			}

	        $array_send['acquirerId'] 				= $this->idacquirer;
	        $array_send['commerceId'] 				= $this->idcommerce;
	        $array_send['purchaseAmount'] 			= $order->order_total;
	        $array_send['purchaseCurrencyCode'] 	= $this->idacquirer;
	        $array_send['purchaseOperationNumber'] 	= $txnid;

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

			$array_send = apply_filters( 'woocommerce_visanetuy_array_send', $array_send );

			return $array_send;

    	}

    	function get_array_get( $order ){

			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Generando array_get ' . $order->get_order_number() );
			}

			$array_get['XMLREQ']="";
			$array_get['DIGITALSIGN']="";
			$array_get['SESSIONKEY']=""; 

			$array_get = apply_filters( 'woocommerce_visanetuy_array_get', $array_get );

			return $array_get;

    	}


	    /**
	     * Generate visanetuy button link
	     **/
	    public function generate_visanetuy_form($order_id){
 
			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Generando formulario de orden for order ' . $order_id->get_order_number() );
			}

	        $order = new WC_Order($order_id);

			if ( 'yes' == $this->testmode ) {
				$visanet_adr = $this->testurl;
			} else {
				$visanet_adr = $this->liveurl;
			}

	        $txnid = $order_id.'_'.date("ymds");
	  
	 		$array_send = $this->get_array_send( $order, $txnid );

	 		$array_get 	= $this->get_array_get( $order ); 

 			VPOSSend( $array_send, $array_get, $this->llavePublica, $this->llavePrivada, substr($this->vector, 0, 16) );

			wc_enqueue_js( '
				$.blockUI({
						message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to PayPal to make payment.', 'woocommerce' ) ) . '",
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
				jQuery("#submit_visanetuy_payment_form").click();
			');

        	return '<form action="' . $visanet_adr . '" method="post" id="visanetuy_payment_form">
	            <input type="hidden" name="IDACQUIRER" value="' . $this->idacquirer . '"/>
	            <input type="hidden" name="IDCOMMERCE" value="' . $this->idcommerce . '"/>
	            <input type="hidden" name="XMLREQ" value="' . $array_get['XMLREQ'] . '"/>
	            <input type="hidden" name="DIGITALSIGN" value="' . $array_get['DIGITALSIGN'] . '"/>
	            <input type="hidden" name="SESSIONKEY" value="' . $array_get['SESSIONKEY'] .'"/>
	            <input type="submit" class="button-alt" id="submit_visanetuy_payment_form" value="'.__('Pay via VisaNetUY', 'woocommerce').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woocommerce').'</a>
	            </form>';
	    }

	    /**
	     * Process the payment and return the result
	     **/
	    function process_payment($order_id){

			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Procesando pago ' . $order_id );
			}

			$order = new WC_Order( $order_id );

			$visanet_args = $this->get_array_get( $order );

			$visanet_args = http_build_query( $visanet_args, '', '&' );

			if ( 'yes' == $this->testmode ) {
				$visanet_adr = $this->testurl;
			} else {
				$visanet_adr = $this->liveurl;
			}

			return array(
				'result' 	=> 'success',
				'redirect'	=> $visanet_adr . '?' . $visanet_args
			);

	    }
 
	    /**
	     * Check for valid visanetuy server callback
	     **/
	    function check_visanetuy_response(){
			if ( 'yes' == $this->debug ) {
				$this->log->add( 'visanet', 'Checking VisaNetUY response is valid.' );
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
	}
   	
   	/**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_visanetuy_gateway($methods) {
        $methods[] = 'WC_VisaNetUY';
        return $methods;
    }
 
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_visanetuy_gateway' );

}
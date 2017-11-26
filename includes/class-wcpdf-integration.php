<?php
if ( ! class_exists( 'wcpdf_Integration_Italian_add_on' )) :

class wcpdf_Integration_Italian_add_on extends WooCommerce_Italian_add_on {

	public function __construct() {
		add_filter( 'wpo_wcpdf_meta_box_actions' , array( $this, 'wcpdf_meta_box_actions') );
		add_filter( 'wpo_wcpdf_listing_actions' , array( $this, 'wcpdf_listing_actions') );
		add_filter( 'wpo_wcpdf_bulk_actions' , array( $this, 'wcpdf_bulk_actions') );
		add_filter( 'wpo_wcpdf_process_template_order' , array( $this, 'wcpdf_process_template_order'), 20,2);
		add_filter( 'wpo_wcpdf_process_order_ids' , array( $this, 'wcpdf_process_order_ids'), 20,2 );
		add_filter( 'wpo_wcpdf_custom_email_condition' , array( $this, 'wcpdf_custom_email_condition'), 20,3);
		add_filter( 'wpo_wcpdf_myaccount_actions', array( $this, 'wcpdf_my_account'), 10, 2 );
		add_filter( 'wpo_wcpdf_template_file', array( $this, 'wcpdf_template_files'), 20, 2 );
		add_filter( 'wpo_wcpdf_attach_documents', array( $this, 'wcpdf_attach_receipt'), 20, 1 );
	}
	
	public function wcpdf_meta_box_actions( $meta_actions ) {
		global $post_id;
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
			$invoicetype = get_post_meta($post_id,"_billing_invoice_type",true);
		} else {
			$order = wc_get_order($post_id);
			$invoicetype = $order->get_meta("_billing_invoice_type",true);
		}
		if($invoicetype == "receipt") {
			$meta_actions = array_merge(array("receipt" => array(
				'url'		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=generate_wpo_wcpdf&template_type=receipt&order_ids=' . $post_id ), 'generate_wpo_wcpdf' ),
				'alt'		=> esc_attr__( 'PDF Receipt', WCPDF_IT_DOMAIN ),
				'title'		=> __( 'PDF Receipt', WCPDF_IT_DOMAIN )
			)), $meta_actions);
			unset($meta_actions['invoice']);
			delete_post_meta( $post_id, '_wcpdf_invoice_number' );
			delete_post_meta( $post_id, '_wcpdf_formatted_invoice_number' );
			delete_post_meta( $post_id, '_wcpdf_invoice_date' );
			delete_post_meta( $post_id, '_wcpdf_invoice_exists' );
		}
		return $meta_actions;
	}
	
	public function wcpdf_listing_actions( $listing_actions) {
		global $the_order ;
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
			$invoicetype = get_post_meta($the_order->id,"_billing_invoice_type",true);
			$order_id = $the_order->id;
		} else {
			$invoicetype = $the_order->get_meta("_billing_invoice_type",true);
			$order_id = $the_order->get_id();
		}
		if($invoicetype == "receipt") {
			$listing_actions = array_merge(array("receipt" => array(
				'url'		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=generate_wpo_wcpdf&template_type=receipt&order_ids=' . $order_id ), 'generate_wpo_wcpdf' ),
				'img'		=> plugins_url() . '/woocommerce-pdf-invoices-italian-add-on/images/receipt.png',
				'alt'		=> __( 'PDF Receipt', WCPDF_IT_DOMAIN )
			)), $listing_actions);
			unset($listing_actions['invoice']);
		}
		return $listing_actions;
	}
	
	public function wcpdf_bulk_actions( $bulk_actions) {
		$bulk_actions['receipt'] = __( 'PDF Receipts', WCPDF_IT_DOMAIN );
		return $bulk_actions;
	}
	
	public function wcpdf_process_template_order($template_type, $order_id) {
		if($template_type == 'invoice') {
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
				$invoicetype = get_post_meta($order_id,"_billing_invoice_type",true);
			} else {
				$order = wc_get_order($order_id);
				$invoicetype = $order->get_meta("_billing_invoice_type",true);
			}
			$template_type = $invoicetype ? $invoicetype : "invoice";
		}
		return $template_type;
	}
	
	public function wcpdf_process_order_ids( $order_ids, $template_type) {
		$oids = array();
		if( !in_array( $template_type, array('invoice', 'receipt') ) ) return($order_ids);
	
		foreach ($order_ids as $order_id) {
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
				$invoicetype = get_post_meta($order_id,"_billing_invoice_type",true);
			} else {
				$order = wc_get_order($order_id);
				$invoicetype = $order->get_meta("_billing_invoice_type",true);
			}
			if((empty($invoicetype) && $template_type == "invoice" ) || ($invoicetype == $template_type)) $oids[] = $order_id;
		}
		return $oids;
	}
	
	public function wcpdf_custom_email_condition($flag, $order, $status) {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
			$invoicetype = get_post_meta($order->id,"_billing_invoice_type",true);
		} else {
			$invoicetype = $order->get_meta("_billing_invoice_type",true);
		}
		return ($invoicetype == "invoice") ? true : false;
	}
	
	public function wcpdf_my_account( $actions, $order ) {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
			$invoicetype = get_post_meta($order->id,"_billing_invoice_type",true);
			$order_id = $order->id;
		} else {
			$invoicetype = $order->get_meta("_billing_invoice_type",true);
			$order_id = $order->get_id();
		}
		
		if ( $invoicetype == 'receipt') {
			$wpo_wcpdf_general_settings = get_option('wpo_wcpdf_general_settings');
			$pdf_url = wp_nonce_url( admin_url( 'admin-ajax.php?action=generate_wpo_wcpdf&template_type=receipt&order_ids=' . $order_id . '&my-account'), 'generate_wpo_wcpdf' );
			if (isset($wpo_wcpdf_general_settings['my_account_buttons'])) {
				switch ($wpo_wcpdf_general_settings['my_account_buttons']) {
					case 'available':
/*
						$invoice_allowed = get_post_meta($order->id,'_wcpdf_invoice_exists',true);
						break;
*/
					case 'always':
						$invoice_allowed = true;
						break;
					case 'never':
						$invoice_allowed = false;
						break;
					case 'custom':
						if ( isset( $wpo_wcpdf_general_settings['my_account_restrict'] ) && in_array( $order->status, array_keys( $wpo_wcpdf_general_settings['my_account_restrict'] ) ) ) {
							$invoice_allowed = true;
						} else {
							$invoice_allowed = false;							
						}
						break;
				}
			} else {
				$invoice_allowed = true; //get_post_meta($order->id,'_wcpdf_invoice_exists',true);
			}
			if ($invoice_allowed) {
				$actions['receipt'] = array(
					'url'  => $pdf_url,
					'name' => __( 'Download Receipt (PDF)', WCPDF_IT_DOMAIN )
				);				
			}
			unset($actions['invoice']);
/*
			$actions['receipt'] = array(
				'url'  => wp_nonce_url( admin_url( 'admin-ajax.php?action=generate_wpo_wcpdf&template_type=receipt&order_ids=' . $order->id . '&my-account' ), 'generate_wpo_wcpdf' ),
				'name' => __( 'Download Receipt (PDF)', WCPDF_IT_DOMAIN )
			);				
*/
		}
		return $actions;
	}
	
	public function wcpdf_template_files( $template, $template_type ) {
		// bail out if file already exists in default or custom path!
		if ( file_exists( $template ) ) {
			return $template;
		}
		
		if ( $template_type == 'receipt') {
			$receipt_template = WooCommerce_Italian_add_on::$plugin_path . 'templates/pdf/Simple/receipt.php';
			if( file_exists( $receipt_template ) ) {
				$template = $receipt_template;
			}
		}
	
		return $template;
	}
	
	public function wcpdf_attach_receipt( $documents ) {
		global $wpo_wcpdf;

		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
			$invoicetype = get_post_meta($wpo_wcpdf->export->order->id,"_billing_invoice_type",true);
		} else {
			$order = wc_get_order($wpo_wcpdf->export->order->get_id() );
			$invoicetype = $order->get_meta("_billing_invoice_type",true);
		}

		if ( $invoicetype == 'receipt') {
			$documents['receipt'] = $documents['invoice'];
			unset($documents['invoice']);
		}
		return $documents;
	}

}
$wcpdf_it_add_on = new wcpdf_Integration_Italian_add_on();
endif;
?>
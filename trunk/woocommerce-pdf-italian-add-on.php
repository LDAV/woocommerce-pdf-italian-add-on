<?php
/**
 * Plugin Name: WooCommerce PDF Invoices Italian Add-on
 * Plugin URI: http://ldav.it/wp/plugins/woocommerce-pdf-italian-add-on/
 * Description: Italian Add-on for PDF invoices & packing slips for WooCommerce.
 * Version: 0.4.1
 * Author: laboratorio d'Avanguardia
 * Author URI: http://ldav.it/
 * License: GPLv2 or later
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 * Text Domain: woocommerce-pdf-italian-add-on

*/

//Thanks to Nicola Mustone https://gist.github.com/SiR-DanieL

function wcpdf_IT_load_plugin_textdomain() {
	$domain = 'woocommerce-pdf-italian-add-on';
	$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
	load_textdomain( $domain, WP_LANG_DIR."/plugins/{$domain}-{$locale}.mo" );
	load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'wcpdf_IT_load_plugin_textdomain' );

/* Add the Invoice or Receipt choice and VAT number fields to WooCommerce checkout*/
add_filter( 'woocommerce_checkout_fields' , 'wcpdf_IT_override_checkout_fields');
function wcpdf_IT_override_checkout_fields( $fields ) {
	$fields['billing']['billing_invoice_type'] = array( 
	'label' => __('Invoice or Receipt', "woocommerce-pdf-italian-add-on"),
	'placeholder' => _x('Invoice or Receipt', 'placeholder', "woocommerce-pdf-italian-add-on"),
	'required' => false,
	'class' => array('form-row-first'),
	'clear'       => false,
	'type'        => 'select',
	'options'     => array(
		'receipt' => __('Receipt', "woocommerce-pdf-italian-add-on" ),
		'invoice' => __('Invoice', "woocommerce-pdf-italian-add-on" )
		)
	);
	$fields['billing']['billing_cf'] = array( 
	'label' => __('VAT number', "woocommerce-pdf-italian-add-on"),
	'placeholder' => _x('Please enter your VAT number or Fiscal code', 'placeholder', "woocommerce-pdf-italian-add-on"),
	'required' => false,
	'class' => array('form-row-last'),
	'clear' => true 
	);
	
	$current_user = wp_get_current_user();
	if($current_user) {
		$fields['billing']['billing_invoice_type']['default'] = $current_user->billing_invoice_type;
		$fields['billing']['billing_cf']['default'] = $current_user->billing_cf;
	}
	return $fields;
}

add_action('woocommerce_checkout_process', 'wcpdf_IT_piva_checkout_field_process');
function wcpdf_IT_piva_checkout_field_process() {
	global $woocommerce;
	// Verifica se è presente quando cliccano su acquista
	if($_POST["billing_invoice_type"] == "invoice") {
		if (!$_POST['billing_cf']) {
			wc_add_notice(__('Please enter your VAT number', "woocommerce-pdf-italian-add-on"),$notice_type = 'error');
		} else {
			if(!preg_match("/^((AT)?U[0-9]{8}|(BE)?0[0-9]{9}|(BG)?[0-9]{9,10}|(CY)?[0-9]{8}L| (CZ)?[0-9]{8,10}|(DE)?[0-9]{9}|(DK)?[0-9]{8}|(EE)?[0-9]{9}|(EL|GR)?[0-9]{9}|(ES)?[0-9A-Z][0-9]{7}[0-9A-Z]|(FI)?[0-9]{8}|(FR)?[0-9A-Z]{2}[0-9]{9}|(GB)?([0-9]{9}([0-9]{3})?|[A-Z]{2}[0-9]{3})|(HU)?[0-9]{8}|(IE)?[0-9]S[0-9]{5}L|(IT)?[0-9]{11}|(LT)?([0-9]{9}|[0-9]{12})|(LU)?[0-9]{8}|(LV)?[0-9]{11}|(MT)?[0-9]{8}|(NL)?[0-9]{9}B[0-9]{2}|(PL)?[0-9]{10}|(PT)?[0-9]{9}|(RO)?[0-9]{2,10}|(SE)?[0-9]{12}|(SI)?[0-9]{8}|(SK)?[0-9]{10})$/i", $_POST["billing_country"].$_POST['billing_cf']))  wc_add_notice(sprintf(__('VAT number %1$s is not correct', "woocommerce-pdf-italian-add-on"), "<strong>". $_POST["billing_country"].$_POST['billing_cf'] . "</strong>"),$notice_type = 'error');
		}
		//if (!$_POST['billing_cf']) wc_add_notice( __('Please enter your VAT number or Fiscal code', "woocommerce-pdf-italian-add-on"),$notice_type = 'error');
	}
	if($_POST["billing_invoice_type"] == "receipt" && $_POST['billing_cf'] && $_POST["billing_country"] == 'IT' && !preg_match("/^([A-Z]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST]{1}[0-9LMNPQRSTUV]{2}[A-Za-z]{1}[0-9LMNPQRSTUV]{3}[A-Z]{1})$/i", $_POST['billing_cf'])) {
		wc_add_notice(sprintf(__('Tax Identification Number %1$s is not correct', "woocommerce-pdf-italian-add-on"), "<strong>". strtoupper($_POST['billing_cf']) . "</strong>"),$notice_type = 'error');
	}
}

/* Add the Invoice or Receipt choice and VAT number fields to WooCommerce Order admin*/
add_filter( 'woocommerce_admin_billing_fields' , 'wcpdf_IT_admin_field_cfpiva' );
function wcpdf_IT_admin_field_cfpiva( $fields ) {
	$fields['invoice_type'] = array(
	'label' => __('Invoice or Receipt', "woocommerce-pdf-italian-add-on"),
	'show' => true, 
	'type'        => 'select',
	'options'     => array(
		'receipt' => __('Receipt', "woocommerce-pdf-italian-add-on" ),
		'invoice' => __('Invoice', "woocommerce-pdf-italian-add-on" )
		)
	);
	$fields['cf'] = array(
	'label' => __('VAT number', "woocommerce-pdf-italian-add-on"),
	'show' => true
	);
	return $fields;
}

add_filter( 'woocommerce_order_formatted_billing_address' , 'wcpdf_IT_woocommerce_order_formatted_billing_address', 10, 2 );
function wcpdf_IT_woocommerce_order_formatted_billing_address( $fields, $order) {
	$fields['invoice_type'] = $order->billing_invoice_type;
	$fields['cf'] = $order->billing_cf;
	return $fields;
}

add_filter( 'woocommerce_my_account_my_address_formatted_address', 'wcpdf_IT_my_account_my_address_formatted_address', 10, 3 );
function wcpdf_IT_my_account_my_address_formatted_address( $fields, $customer_id, $type ) {
	if ( $type == 'billing' ) {
		$fields['invoice_type'] = get_user_meta( $customer_id, 'billing_invoice_type', true );
		$fields['cf'] = get_user_meta( $customer_id, 'billing_cf', true );
	}
	return $fields;
}

add_filter( 'woocommerce_address_to_edit', 'wcpdf_IT_address_to_edit' );
function wcpdf_IT_address_to_edit( $address ) {
	global $wp_query;

	if ( isset( $wp_query->query_vars['edit-address'] ) && $wp_query->query_vars['edit-address'] != 'billing' ) {
		return $address;
	}
	
	if ( ! isset( $address['billing_invoice_type'] ) ) {
    	$address['billing_invoice_type'] = array(
				'label' => __('Invoice or Receipt', "woocommerce-pdf-italian-add-on"),
				'placeholder' => _x( 'Invoice or Receipt', 'placeholder', 'woocommerce-pdf-italian-add-on' ),
				'required'    => false,
				'class'       => array( 'form-row-first' ),
				'clear'       => false,
				'type'        => 'select',
				'options'     => array(
					'receipt' => __('Receipt', "woocommerce-pdf-italian-add-on" ),
					'invoice' => __('Invoice', "woocommerce-pdf-italian-add-on" )
				),
				'value'       => get_user_meta( get_current_user_id(), 'billing_invoice_type', true )
      );
    }

    if ( ! isset( $address['billing_cf'] ) ) {
    	$address['billing_cf'] = array(
				'label'       => __('VAT number', "woocommerce-pdf-italian-add-on"),
				'placeholder' => _x( 'VAT number', 'placeholder', 'woocommerce-pdf-italian-add-on' ),
				'required'    => false,
				'class'       => array( 'form-row-first' ),
				'value'       => get_user_meta( get_current_user_id(), 'billing_cf', true )
			);
    }

    return $address;
}

add_filter( 'woocommerce_formatted_address_replacements', 'wcpdf_IT_formatted_address_replacements', 10, 2 );
function wcpdf_IT_formatted_address_replacements( $address, $args ) {
	$address['{invoice_type}'] = '';
	$address['{cf}'] = '';

	if ( ! empty( $args['cf']) && ! empty( $args['invoice_type'] ) ) {
		$address['{cf}'] = ($args['invoice_type'] == "invoice" ? __('VAT', "woocommerce-pdf-italian-add-on") . ": " . $args['country'] : __('Fiscal code', "woocommerce-pdf-italian-add-on") . ': ') . strtoupper( $args['cf'] );
	}

	return $address;
}

add_filter( 'woocommerce_localisation_address_formats', 'wcpdf_IT_localisation_address_format' );
function wcpdf_IT_localisation_address_format( $formats ) {
	$formats['IT'] .= "\n\n{cf}";
	return $formats;
}

add_filter( 'woocommerce_found_customer_details', 'wcpdf_IT_found_customer_details' );
function wcpdf_IT_found_customer_details( $customer_data ) {
	$customer_data['billing_invoice_type'] = get_user_meta( $_POST['user_id'], 'billing_invoice_type', true );
	$customer_data['billing_cf'] = get_user_meta( $_POST['user_id'], 'billing_cf', true );
	return $customer_data;
}

add_filter( 'woocommerce_customer_meta_fields', 'wcpdf_IT_customer_meta_fields' );
function wcpdf_IT_customer_meta_fields( $fields ) {
	$fields['billing']['fields']['billing_invoice_type'] = array(
		'label'       => __('Invoice or Receipt', "woocommerce-pdf-italian-add-on"),
		'type'        => 'select',
		'options'     => array(
			'receipt' => __('Receipt', "woocommerce-pdf-italian-add-on" ),
			'invoice' => __('Invoice', "woocommerce-pdf-italian-add-on" )
		),
		'description'       => ""
	);
	$fields['billing']['fields']['billing_cf'] = array(
		'label'       => __('VAT number', "woocommerce-pdf-italian-add-on"),
		'description'       => ""
	);
	return $fields;
}

add_filter( 'wpo_wcpdf_meta_box_actions' , 'wcpdf_IT_wpo_wcpdf_meta_box_actions' );
function wcpdf_IT_wpo_wcpdf_meta_box_actions( $meta_actions ) {
	global $post_id;
	$invoicetype = get_post_meta($post_id,"_billing_invoice_type",true);
	if($invoicetype == "receipt") {
		$meta_actions = array_merge(array("receipt" => array(
			'url'		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=generate_wpo_wcpdf&template_type=receipt&order_ids=' . $post_id ), 'generate_wpo_wcpdf' ),
			'alt'		=> esc_attr__( 'PDF Receipt', 'woocommerce-pdf-italian-add-on' ),
			'title'		=> __( 'PDF Receipt', 'woocommerce-pdf-italian-add-on' )
		)), $meta_actions);
		unset($meta_actions['invoice']);
		delete_post_meta( $post_id, '_wcpdf_invoice_exists' );
		delete_post_meta( $post_id, '_wcpdf_invoice_date' );
		delete_post_meta( $post_id, '_wcpdf_invoice_number' );
	}
	return $meta_actions;
}

add_filter( 'wpo_wcpdf_listing_actions' , 'wcpdf_IT_wpo_wcpdf_listing_actions' );
function wcpdf_IT_wpo_wcpdf_listing_actions( $listing_actions) {
	global $the_order ;
	$invoicetype = get_post_meta($the_order->id,"_billing_invoice_type",true);
	if($invoicetype == "receipt") {
		$listing_actions = array_merge(array("receipt" => array(
			'url'		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=generate_wpo_wcpdf&template_type=receipt&order_ids=' . $the_order->id ), 'generate_wpo_wcpdf' ),
			'img'		=> plugins_url() . '/woocommerce-pdf-invoices-italian-add-on/images/receipt.png',
			'alt'		=> __( 'PDF Receipt', 'woocommerce-pdf-italian-add-on' )
		)), $listing_actions);
		unset($listing_actions['invoice']);
	}
	return $listing_actions;
}

add_filter( 'wpo_wcpdf_bulk_actions' , 'wcpdf_IT_wpo_wcpdf_bulk_actions' );
function wcpdf_IT_wpo_wcpdf_bulk_actions( $bulk_actions) {
	$bulk_actions['receipt'] = __( 'PDF Receipts', 'woocommerce-pdf-italian-add-on' );
	return $bulk_actions;
}

add_filter( 'wpo_wcpdf_process_template_order' , 'wcpdf_IT_wpo_wcpdf_process_template_order', 20,2);
function wcpdf_IT_wpo_wcpdf_process_template_order($template_type, $order_id) {
	if($template_type == 'invoice') {
		$invoicetype = get_post_meta($order_id,"_billing_invoice_type",true);
		$template_type = $invoicetype ? $invoicetype : "invoice";
	}
	return $template_type;
}

add_filter( 'wpo_wcpdf_process_order_ids' , 'wcpdf_IT_wpo_wcpdf_process_order_ids', 20,2 );
function wcpdf_IT_wpo_wcpdf_process_order_ids( $order_ids, $template_type) {
	$oids = array();
	if($template_type == "packing-slip") return($order_ids);

	foreach ($order_ids as $order_id) {
		$invoicetype = get_post_meta($order_id,"_billing_invoice_type",true);
		if($invoicetype == $template_type) $oids[] = $order_id;
	}
	return $oids;
}

add_filter( 'wpo_wcpdf_custom_email_condition' , 'wcpdf_IT_wpo_wcpdf_custom_email_condition', 20,3);
function wcpdf_IT_wpo_wcpdf_custom_email_condition($flag, $order, $status) {
	$invoicetype = get_post_meta($order->id,"_billing_invoice_type",true);
	return ($invoicetype == "invoice") ? true : false;
}

add_filter( 'wpo_wcpdf_myaccount_actions', 'wcpdf_IT_wpo_wcpdf_my_account', 10, 2 );
function wcpdf_IT_wpo_wcpdf_my_account( $actions, $order ) {
	$invoicetype = get_post_meta($order->id,"_billing_invoice_type",true);
	if ( $invoicetype == 'receipt') {
		$actions['receipt'] = array(
			'url'  => wp_nonce_url( admin_url( 'admin-ajax.php?action=generate_wpo_wcpdf&template_type=receipt&order_ids=' . $order->id . '&my-account' ), 'generate_wpo_wcpdf' ),
			'name' => __( 'Download Receipt (PDF)', 'woocommerce-pdf-italian-add-on' )
		);				
		unset($actions['invoice']);
	}
	return $actions;
}

add_filter( 'wpo_wcpdf_template_file', 'wcpdf_IT_wpo_wcpdf_template_files', 20, 2 );
function wcpdf_IT_wpo_wcpdf_template_files( $template, $template_type ) {
	global $wpo_wcpdf;
	$template = $wpo_wcpdf->export->template_path . '/' . $template_type . '.php';
	if( file_exists( $template ) ) return $template;
	$receipt_template = dirname(__FILE__) . '/templates/pdf/Simple/receipt.php';
	if( file_exists( $receipt_template ) ) return $receipt_template;
	return $template;
}

add_filter( 'wpo_wcpdf_attach_documents', 'wcpdf_IT_wpo_wcpdf_attach_receipt', 20, 1 );
function wcpdf_IT_wpo_wcpdf_attach_receipt( $documents ) {
	global $wpo_wcpdf;
	$invoicetype = get_post_meta($wpo_wcpdf->export->order->id,"_billing_invoice_type",true);
	if ( $invoicetype == 'receipt') {
		$documents['receipt'] = $documents['invoice'];
		unset($documents['invoice']);
	}
	return $documents;
}

<?php
/**
* Plugin Name: WooCommerce PDF Invoices Italian Add-on
* Plugin URI: https://ldav.it/plugin/woocommerce-pdf-invoices-italian-add-on/
* Description: Aggiunge a WooCommerce tutto il necessario per un e-commerce italiano e la fatturazione elettronica
* Version: 0.7.6.1
* Author: laboratorio d'Avanguardia
* Author URI: https://ldav.it/
* License: GPLv2 or later
* License URI: http://www.opensource.org/licenses/gpl-license.php
* Text Domain: woocommerce-pdf-italian-add-on
* Domain Path: /languages
* Requires Plugins: woocommerce
* WC requires at least: 8.0
* WC tested up to: 9.4.1
*/

//Thanks to Nicola Mustone https://gist.github.com/SiR-DanieL

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( !class_exists( 'WooCommerce_Italian_add_on' ) ) :
if ( ! defined( 'WCPDF_IT_DOMAIN' ) ) define('WCPDF_IT_DOMAIN', 'woocommerce-pdf-italian-add-on');

class WooCommerce_Italian_add_on {
	public static $plugin_url;
	public static $plugin_path;
	public static $plugin_basename;
	public $version = '0.7.6.1';
	protected static $instance = null;
	
	public $settings;
	public $options;
	public $fpa_options;
	public $eu_vat_countries;
	public $default_country;
	public $has_error;
	public $what_if_no_invoicetype;
	public $fields_priority;
	public $regexCF = "/^([A-Z]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST]{1}[0-9LMNPQRSTUV]{2}[A-Za-z]{1}[0-9LMNPQRSTUV]{3}[A-Z]{1})$/i";
	public $regexPIVA = "/^(ATU[0-9]{8}|BE0[0-9]{9}|BG[0-9]{9,10}|CY[0-9]{8}[A-Z]|CZ[0-9]{8,10}|DE[0-9]{9}|DK[0-9]{8}|EE[0-9]{9}|(EL|GR)[0-9]{9}|ES[0-9A-Z][0-9]{7}[0-9A-Z]|FI[0-9]{8}|FR[0-9A-Z]{2}[0-9]{9}|GB([0-9]{9}([0-9]{3})?|[A-Z]{2}[0-9]{13})|HR[0-9]{11}|HU[0-9]{8}|IE[0-9][A-Z0-9][0-9]{5}[A-Z]{1,2}|IT[0-9]{11}|LT([0-9]{9}|[0-9]{12})|LU[0-9]{8}|LV[0-9]{11}|MT[0-9]{8}|NL[0-9]{9}B[0-9]{2}|PL[0-9]{10}|PT[0-9]{9}|RO[0-9]{2,10}|SE[0-9]{12}|SI[0-9]{8}|SM[0-9]{3,5}|SK[0-9]{10})$/i";
	public $regexCodiceDestinatario = "/^[A-Z0-9]{6,7}$/i";
	
	public $invoice_required;
	public $hide_outside_UE;
	public $invoice_required_non_UE;
	public $add_cf2;
	public $add_PEC;

	public static function instance() {
		if ( is_null( self::$instance ) ) self::$instance = new self();
		return self::$instance;
	}

	public function __construct() {
		self::$plugin_basename = plugin_basename(__FILE__);
		self::$plugin_url = plugin_dir_url(self::$plugin_basename);
		self::$plugin_path = trailingslashit(dirname(__FILE__));
		add_action( 'before_woocommerce_init', array( $this, 'woocommerce_hpos_compatible' ) );
		$this->init_hooks();
	}
	
	public function woocommerce_hpos_compatible() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	public function init() {
		$locale = apply_filters( "plugin_locale", get_locale(), WCPDF_IT_DOMAIN );
		$langdir    = trailingslashit( WP_LANG_DIR );
		load_textdomain( WCPDF_IT_DOMAIN, $langdir . dirname( self::$plugin_basename ). "/" . WCPDF_IT_DOMAIN . "-" . $locale . ".mo" );
		load_textdomain( WCPDF_IT_DOMAIN, $langdir . "plugins/" . WCPDF_IT_DOMAIN . "-" . $locale . ".mo" );
		load_plugin_textdomain( WCPDF_IT_DOMAIN, false, dirname( self::$plugin_basename ) . "/languages" );
	}

	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		add_action( 'init', array( $this, 'init_integration' ) );
		add_filter( 'woocommerce_billing_fields' , array( $this, 'billing_fields'), 10, 1);
		add_filter( 'woocommerce_admin_billing_fields' , array( $this, 'admin_billing_fields' ));
		add_action( 'woocommerce_after_edit_address_form_billing', array( $this, 'after_edit_address_form_billing') );
		add_action( 'woocommerce_after_order_notes', array( $this, 'after_order_notes') );
		add_action( 'woocommerce_checkout_fields', array( $this, 'checkout_fields'));
		add_action( 'woocommerce_checkout_process', array( $this, 'piva_checkout_field_process'));
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'checkout_update_order_meta'));
		add_filter( 'woocommerce_order_formatted_billing_address' , array( $this, 'woocommerce_order_formatted_billing_address'), 10, 2 );
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'my_account_my_address_formatted_address'), 10, 3 );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'formatted_address_replacements'), 10, 2 );
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'localisation_address_format') );
		add_action( 'woocommerce_get_order_address', array($this, 'get_order_address'), 10, 3 );
		add_filter( 'woocommerce_ajax_get_customer_details', array( $this, 'ajax_get_customer_details'), 10, 3 );
		add_filter( 'woocommerce_customer_meta_fields', array( $this, 'customer_meta_fields') );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_invoice_type_column' ), 999 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'invoice_type_column_data' ), 10, 2 );
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_invoice_type_column' ));
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'invoice_type_column_data' ), 10, 2);
	}

	public function init_integration() {
		include_once 'includes/class-wc-settings.php';
		$this->settings = new WooCommerce_Italian_add_on_Settings();
		$this->options = get_option($this->settings->general_settings_key);
		$this->fpa_options = get_option($this->settings->fpa_settings_key);
		
		$this->invoice_required = isset($this->options["invoice_required"]) && $this->options["invoice_required"] == "required" ? true: false;
		$this->what_if_no_invoicetype = isset($this->options["what_if_no_invoicetype"]) ? $this->options["what_if_no_invoicetype"] : "noinvoice";
		
		$this->hide_outside_UE = !empty($this->options["hide_outside_UE"]);
		$this->invoice_required_non_UE = !empty($this->options["invoice_required_non_UE"]);
		$this->has_error = false;
		$this->fields_priority = empty($this->options["fields_priority"]) ? 120 : intval($this->options["fields_priority"]);
		$this->add_cf2 = empty($this->options["add_cf2"]) ? 0 : intval($this->options["add_cf2"]);
		$this->add_PEC = empty($this->fpa_options["add_FPA"]) ? 0 : 1;

		$this->default_country = WC()->countries->get_base_country();
		$this->eu_vat_countries = WC()->countries->get_european_union_countries();
		if($this->default_country == "IT") $this->eu_vat_countries[] = "SM"; // aggiunge San Marino
			
		include_once 'includes/class_wc_update_db.php';
		$update = new WooCommerce_Italian_add_on_Update();
		$update->test();

		if ( class_exists( 'WPO_WCPDF' ) ) {
			if(version_compare( WPO_WCPDF()->version, '2.0', '<' ) ) {
				include_once 'includes/class-wcpdf-integration.php';
			} elseif(version_compare( WPO_WCPDF()->version, '2.7', '<' ) ) {
				include_once 'includes/class-wcpdf-integration2.php';
			} else {
				include_once 'includes/class-wcpdf-integration3.php';
			}
/*
		} else {
			add_action( 'admin_notices', array ( $this, 'check_wpo_wcpdf' ) );
*/
		}
	}

	// thanks to Andrea Grillo, YITHemes member
	public function get_order_address($address, $type, $order){
		/**
		 * Filter get address method for WC_Order
		 *
		 * @author Andrea Grillo <info@andreagrillo.it>
		 * @param array $address
		 * @param string $type
		 * @param \WC_Order $order
		 * @return array
	 */
		if( 'billing' == $type ){
			$custom_fields = array( '_billing_invoice_type', '_billing_cf' );
			if($this->add_cf2) $custom_fields[] = "_billing_cf2";
			if($this->add_PEC) $custom_fields[] = "_billing_PEC";
			foreach( $custom_fields as $key ) {
				$value = $order->get_meta( $key );
				$key = str_replace( '_' .$type . '_', '', $key );
				$value && $address[$key] = $value;
			}
		}
		return $address;
	}

	public function billing_fields( $fields ) {
		$cl1 = $this->options["invoice_width"] == "wide" ? 'form-row-wide' : 'form-row-first';
		$cl2 = $this->options["invoice_width"] == "wide" ? 'form-row-wide' : 'form-row-last';
		$req = $this->options["invoice_required"] == "required" ? true: false;
		
		$fields['billing_invoice_type'] = array(
			'label' => __('Invoice or Receipt', WCPDF_IT_DOMAIN),
			'placeholder' => __( 'Invoice or Receipt', WCPDF_IT_DOMAIN ),
			'required'    => false,
			'class'       => array( $cl1, 'update_totals_on_change'),
			'clear'       => false,
			'priority' => $this->fields_priority,
			'type'        => 'select',
			'options'     => array(
				'receipt' => __('Receipt', WCPDF_IT_DOMAIN ),
				'invoice' => __('Invoice', WCPDF_IT_DOMAIN )
			),
			'value'       => get_user_meta( get_current_user_id(), 'billing_invoice_type', true )
		);
		
		$fields['billing_customer_type'] = array(
			'label' => __('Personal or Business', WCPDF_IT_DOMAIN),
			'placeholder' => __( 'Personal or Business', WCPDF_IT_DOMAIN ),
			'required'    => false,
			'class'       => array("hidden", "wcpdf_IT_hidden"),
			'clear'       => false,
			'priority' => $this->fields_priority,
			'type'        => 'hidden',
			'value'       => get_user_meta( get_current_user_id(), 'billing_customer_type', true )
		);
		
		$fields['billing_cf'] = array(
			'label'       => __('VAT number or Tax Code', WCPDF_IT_DOMAIN),
			'placeholder' => __('Please enter your VAT number or Tax Code', WCPDF_IT_DOMAIN),
			'required'    => $req,
			'class'       => array( $cl2 ),
			'priority' => $this->fields_priority,
			'value'       => get_user_meta( get_current_user_id(), 'billing_cf', true )
		);
		if($this->add_cf2) {
			$fields['billing_cf2'] = array(
				'label'       => __('Tax Code', WCPDF_IT_DOMAIN),
				'placeholder' => __('Please enter your Tax Code', WCPDF_IT_DOMAIN),
				'required'    => false, //va inizialmente impostato non obbligatorio, per poterlo attivare solo nei casi richiesti
				'class'       => array( "hidden", "wcpdf_IT_hidden" ),
				'priority' => $this->fields_priority,
				'value'       => get_user_meta( get_current_user_id(), 'billing_cf2', true )
			);
		}
		if($this->add_PEC) {
			$fields['billing_PEC'] = array(
				'label'       => __('Certified Email Address or Recipient Code', WCPDF_IT_DOMAIN),
				'placeholder' => __('Please enter your Certified Email Address or Recipient Code', WCPDF_IT_DOMAIN),
				'required'    => false, //va inizialmente impostato non obbligatorio, per poterlo attivare solo nei casi richiesti
				'class'       => array( "hidden", "wcpdf_IT_hidden" ),
				'priority' => $this->fields_priority,
				'value'       => get_user_meta( get_current_user_id(), 'billing_PEC', true )
			);
		}
		return $fields;
	}
	
	public function admin_billing_fields( $fields ) {
		$fields['invoice_type'] = array(
		'label' => __('Invoice or Receipt', WCPDF_IT_DOMAIN),
		'show' => false,
		'wrapper_class' => 'form-field-wide',
		'type'        => 'select',
		'options'     => array(
			'' => __('Not set', WCPDF_IT_DOMAIN ),
			'receipt' => __('Receipt', WCPDF_IT_DOMAIN ),
			'invoice' => __('Invoice', WCPDF_IT_DOMAIN )
			)
		);
		$fields['customer_type'] = array(
		'label' => __('Personal or Business', WCPDF_IT_DOMAIN),
		'show' => false,
		'wrapper_class' => 'form-field-wide',
		'type'        => 'select',
		'options'     => array(
			'' => __('Not set', WCPDF_IT_DOMAIN ),
			'personal' => __('Personal', WCPDF_IT_DOMAIN ),
			'business' => __('Business', WCPDF_IT_DOMAIN )
			)
		);
		$fields['cf'] = array(
		'label' => __('VAT number or Tax Code', WCPDF_IT_DOMAIN),
		'wrapper_class' => 'form-field-wide',
		'show' => false
		);
		
		if($this->add_cf2) {
			$fields['cf2'] = array(
				'label'       => __('Tax Code', WCPDF_IT_DOMAIN),
				'wrapper_class' => 'form-field-wide',
				'show' => false
			);
		}
		if($this->add_PEC) {
			$fields['PEC'] = array(
				'label'       => __('Certified Email Address or Recipient Code', WCPDF_IT_DOMAIN),
				'wrapper_class' => 'form-field-wide',
				'show' => false
			);
		}
	
		return $fields;
	}
	
	public function add_js_and_fields($customer_type){
		wp_register_script( 'wc_italian_add_on', self::$plugin_url.'includes/checkout.js' );
		wp_localize_script( 'wc_italian_add_on', 'wcpdf_IT', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'eu_vat_countries' => $this->eu_vat_countries,
			'hide_outside_UE' => ($this->hide_outside_UE ? true : false),
			'invoice_required' => ($this->invoice_required ? true : false),
			'invoice_required_non_UE' => ($this->invoice_required_non_UE ? true : false),
			'add_cf2' => $this->add_cf2,
			'add_PEC' => $this->add_PEC,
			'has_error' => ($this->has_error ? true : false),
			'lblCommon' => __('VAT number or Tax Code', WCPDF_IT_DOMAIN),
			'txtCommon' => __('Please enter your VAT number or Tax Code', WCPDF_IT_DOMAIN),
			'lblPersonal' => __('Tax Code', WCPDF_IT_DOMAIN),
			'txtPersonal' => __('Please enter your Tax Code', WCPDF_IT_DOMAIN),
			'lblBusiness' => __('VAT number', WCPDF_IT_DOMAIN),
			'txtBusiness' => __('Please enter your VAT number', WCPDF_IT_DOMAIN),
			'required_text' => ' <abbr class="required" title="' . __("required", "woocommerce") . '">*</abbr>'
		) );
		wp_enqueue_script( 'wc_italian_add_on' );
		//echo '<input type="hidden" name="billing_customer_type" id="billing_customer_type" value="' . $customer_type . '">'; //WC 4.5 supports hidden fields
		echo '<style>.wcpdf_IT_hidden{display:none !important}</style>';
	}

	public function after_edit_address_form_billing() {
		$billing_customer_type = get_user_meta( get_current_user_id(), 'billing_customer_type', true );
		$customer_type = empty($billing_customer_type) ? "personal" : $billing_customer_type;
		$this->add_js_and_fields($customer_type);
	}

	public function after_order_notes($checkout) {
		$billing_customer_type = $checkout->get_value( 'billing_customer_type' );
		$customer_type = empty($billing_customer_type) ? "personal" : $billing_customer_type;
		$this->add_js_and_fields($customer_type);
	}
	
	public function checkout_fields($fields) {
		if(isset($_POST["billing_country"]) && $this->invoice_required) {
			$fields['billing']['billing_cf']['required'] = (in_array($_POST["billing_country"], $this->eu_vat_countries) || (!$this->hide_outside_UE && $this->invoice_required_non_UE) );
		}
		return($fields);
	}
	
	public function checkout_update_order_meta($order_id) {
		if (!empty($_POST['billing_cf'])) {
			$value = strlen($_POST['billing_cf']) > 15 ? "personal" : "business";
			$order = wc_get_order( $order_id );
			$order->update_meta_data( 'billing_customer_type', $value );
			$order->save();
    }
	}
	
	public function piva_checkout_field_process() {
		$_POST = apply_filters( 'wcpdf_IT_before_checkout_process', $_POST, $this );
		$_POST["billing_cf"] = !empty($_POST["billing_cf"]) ? preg_replace("/[^0-9A-Za-z]+/i", "", $_POST["billing_cf"]) : "";
		$_POST["billing_cf2"] = !empty($_POST["billing_cf2"]) ? preg_replace("/[^0-9A-Za-z]+/i", "", $_POST["billing_cf2"]) : "";
		$_POST["billing_PEC"] = !empty($_POST["billing_PEC"]) ? preg_replace("/[^0-9A-Za-z@_\-\.]+/i", "", $_POST["billing_PEC"]) : "";

		if($_POST["billing_invoice_type"] == "invoice") {
			if($_POST["billing_country"] == 'IT' && strlen($_POST['billing_cf']) > 13) {
				if(!preg_match($this->regexCF, $_POST['billing_cf'])) {
					wc_add_notice(sprintf(__('Tax Identification Number %1$s is not correct', WCPDF_IT_DOMAIN), "<strong>". strtoupper($_POST['billing_cf']) . "</strong>"),$notice_type = 'error');
					$this->has_error = true;
				}
			} elseif(in_array($_POST["billing_country"], $this->eu_vat_countries)) {
				if(strtoupper(substr($_POST['billing_cf'],0,2)) == $_POST["billing_country"]) $_POST['billing_cf'] = substr($_POST['billing_cf'],2);
				if(empty($_POST['billing_cf']) || strlen($_POST['billing_cf']) < 8) {
					wc_add_notice(__('Please enter your VAT number or Tax Code', WCPDF_IT_DOMAIN),$notice_type = 'error');
					$this->has_error = true;
				} elseif(!preg_match($this->regexPIVA, $_POST["billing_country"].$_POST['billing_cf'])) {
					wc_add_notice(sprintf(__('VAT number %1$s is not correct', WCPDF_IT_DOMAIN), "<strong>". $_POST["billing_country"]."-".$_POST['billing_cf'] . "</strong>"),$notice_type = 'error');
					$this->has_error = true;
				}
			} else {
				if($this->invoice_required_non_UE && !$_POST['billing_cf']) {
					wc_add_notice(__('Please enter your VAT number', WCPDF_IT_DOMAIN) ,$notice_type = 'error');
					$this->has_error = true;
				}
			}
		}
		
		if($this->add_cf2 > 0 && $_POST["billing_invoice_type"] == "invoice" && $_POST["billing_country"] == 'IT') {
			if( (!empty($_POST["billing_cf2"]) && !(preg_match($this->regexCF, $_POST['billing_cf2']) || preg_match($this->regexPIVA, "IT" . $_POST['billing_cf2']) ) ) ||
					(empty($_POST["billing_cf2"]) && $this->add_cf2 == 2) ) {
				wc_add_notice(sprintf(__('<strong>Tax Code %1$s</strong> is not correct', WCPDF_IT_DOMAIN) , "<strong>". strtoupper($_POST['billing_cf2']) . "</strong>" . "<!-- wcpdf_IT_error_billing_cf2 -->"), $notice_type = 'error');
			}
		}

		if($this->add_PEC && $_POST["billing_invoice_type"] == "invoice" && $_POST["billing_country"] == 'IT' && strlen($_POST['billing_cf']) < 16 && strlen($_POST['billing_cf']) > 0) {
			if( empty($_POST["billing_PEC"]) || 
				 !(is_email($_POST['billing_PEC']) || preg_match($this->regexCodiceDestinatario,  $_POST['billing_PEC']) ) ) {
				wc_add_notice(sprintf(__('<strong>Certified Email Address or Recipient Code %1$s</strong> is not correct', WCPDF_IT_DOMAIN) , "<strong>". strtolower($_POST['billing_PEC']) . "</strong>" . "<!-- wcpdf_IT_error_billing_PEC -->"), $notice_type = 'error');
			}
		}

		if($_POST["billing_invoice_type"] == "receipt" && $_POST['billing_cf'] && $_POST["billing_country"] == 'IT'){
			if(!preg_match($this->regexCF, $_POST['billing_cf']) && !preg_match("/^([0-9]{11})$/i", $_POST['billing_cf'])) {
				wc_add_notice(sprintf(__('Tax Identification Number %1$s is not correct', WCPDF_IT_DOMAIN), "<strong>". strtoupper($_POST['billing_cf']) . "</strong>"),$notice_type = 'error');
				$this->has_error = true;
			}
		}
		do_action( 'wcpdf_IT_after_checkout_process', $this, $_POST );
	}

	public function woocommerce_order_formatted_billing_address( $fields, $order) {
		$fields['customer_type'] = wcpdf_it_get_billing_customer_type($order);
		$fields['invoice_type'] = wcpdf_it_get_billing_invoice_type($order);
		$fields['cf'] = wcpdf_it_get_billing_cf($order);
		if($this->add_cf2) $fields['cf2'] = wcpdf_it_get_billing_cf2($order);
		if($this->add_PEC) $fields['PEC'] = wcpdf_it_get_billing_PEC($order);
		return $fields;
	}
	
	public function my_account_my_address_formatted_address( $fields, $customer_id, $type ) {
		if ( $type == 'billing' ) {
			$fields['customer_type'] = get_user_meta( $customer_id, 'billing_customer_type', true );
			$fields['invoice_type'] = get_user_meta( $customer_id, 'billing_invoice_type', true );
			$fields['cf'] = get_user_meta( $customer_id, 'billing_cf', true );
			if($this->add_cf2) $fields['cf2'] = get_user_meta( $customer_id, 'billing_cf2', true );
			if($this->add_PEC) $fields['PEC'] = get_user_meta( $customer_id, 'billing_PEC', true );
		}
		return $fields;
	}
	
	public function formatted_address_replacements( $address, $args ) {
		$address['{customer_type}'] = '';
		$address['{invoice_type}'] = '';
		$address['{cf}'] = '';
		$address['{cf2}'] = '';
		$address['{PEC}'] = '';
	
		if (!empty($args['cf'])) {
			$pre = preg_match($this->regexCF, $args['cf']) ? __('Tax Code', WCPDF_IT_DOMAIN) . ': ' : __('VAT', WCPDF_IT_DOMAIN) . ": " . ((in_array($args['country'], $this->eu_vat_countries)) ? $args['country'] : "");
			$address['{cf}'] = $pre . strtoupper($args['cf']);
		}
		
		if(!empty($args['invoice_type']) && $args['invoice_type'] == "invoice") {
			if($this->add_cf2) {
				if ( ! empty( $args['cf2']) ) {
					$pre = __('Tax Code', WCPDF_IT_DOMAIN) . ': ';
					$address['{cf2}'] = $pre . strtoupper($args['cf2']);
				}
			}

			if($this->add_PEC) {
				if ( ! empty( $args['PEC']) ) {
					$pre = preg_match($this->regexCodiceDestinatario, $args['PEC']) ? __('Recipient Code', WCPDF_IT_DOMAIN) : __('Certified Email Address', WCPDF_IT_DOMAIN);
					$address['{PEC}'] = $pre . ': ' . strtolower($args['PEC']);
				}
			}
		}

		return $address;
	}
	
	public function localisation_address_format( $formats ) {
		foreach($formats as $country => $value) {
			$formats[$country] = $value . "\n{cf}";
			if($this->add_cf2) $formats[$country] .= "\n{cf2}";
			if($this->add_PEC) $formats[$country] .= "\n{PEC}";
		}
		return $formats;
	}

	public function ajax_get_customer_details($data, $customer, $user_id) {
		$data['billing']['invoice_type'] = get_user_meta( $user_id, 'billing_invoice_type', true );
		$data['billing']['customer_type'] = get_user_meta( $user_id, 'billing_customer_type', true );
		$data['billing']["cf"] = get_user_meta( $user_id, 'billing_cf', true );
		if($this->add_cf2) $data['billing']["cf2"] = get_user_meta( $user_id, 'billing_cf2', true );
		if($this->add_PEC) $data['billing']["PEC"] = get_user_meta( $user_id, 'billing_PEC', true );
		return $data;
	}

	public function found_customer_details( $customer_data ) {
		$customer_data['billing_invoice_type'] = get_user_meta( $_POST['user_id'], 'billing_invoice_type', true );
		$customer_data['billing_customer_type'] = get_user_meta( $_POST['user_id'], 'billing_customer_type', true );
		$customer_data['billing_cf'] = get_user_meta( $_POST['user_id'], 'billing_cf', true );
		if($this->add_cf2) $customer_data['billing_cf2'] = get_user_meta( $_POST['user_id'], 'billing_cf2', true );
		if($this->add_PEC) $customer_data['billing_PEC'] = get_user_meta( $_POST['user_id'], 'billing_PEC', true );
		return $customer_data;
	}
	
	/* Add fields in Edit User Page*/
	public function customer_meta_fields( $fields ) {
		$fields['billing']['fields']['billing_invoice_type'] = array(
			'label'       => __('Invoice or Receipt', WCPDF_IT_DOMAIN),
			'type'        => 'select',
			'class' => '',
			'options'     => array(
				'receipt' => __('Receipt', WCPDF_IT_DOMAIN ),
				'invoice' => __('Invoice', WCPDF_IT_DOMAIN )
			),
			'description'       => ""
		);
		$fields['billing']['fields']['billing_cf'] = array(
			'label'       => __('VAT number', WCPDF_IT_DOMAIN),
			'class' => 'regular-text',
			'description'       => ""
		);
		if($this->add_cf2) {
			$fields['billing']['fields']['billing_cf2'] = array(
				'label'       => __('Tax Code', WCPDF_IT_DOMAIN),
				'class' => 'regular-text',
				'description'       => ""
			);
		}
		if($this->add_PEC) {
			$fields['billing']['fields']['billing_PEC'] = array(
				'label'       => __('Certified Email Address or Recipient Code', WCPDF_IT_DOMAIN),
				'class' => 'regular-text',
				'description'       => ""
			);
		}
		return $fields;
	}

	public function add_invoice_type_column( $columns ) {
		$new_columns = array_slice($columns, 0, 2, true) +
			array( 'invoice_type' => '<span class="status_head tips" data-tip="' . __( 'Invoice or Receipt', WCPDF_IT_DOMAIN ) . '">' . __( 'Invoice or Receipt', WCPDF_IT_DOMAIN ) . '</span>') +
			array_slice($columns, 2, count($columns) - 1, true) ;
?>
<style>
table.wp-list-table .column-invoice_type{width:48px; text-align:center; color:#999}
.manage-column.column-invoice_type span.status_head:after{content:"\e00f"}
</style>
<?php
		return $new_columns;
	}

	public function get_order_id($order) {
		$order_id = $order->get_id();
		return($order_id);
	}
	
	public function invoice_type_column_data( $column, $order ) {
		if ( $column === 'invoice_type' ) {
			$invoicetype = wcpdf_it_get_billing_invoice_type($order);
			switch($invoicetype) {
			case "invoice": echo "<i class=\"dashicons dashicons-media-document tips\" data-tip=\"" . esc_attr__( 'invoice', WCPDF_IT_DOMAIN ) . "\"></i>"; break;
				case "receipt": echo "<i class=\"dashicons dashicons-media-default tips\" data-tip=\"" . esc_attr__( 'receipt', WCPDF_IT_DOMAIN ) . "\"></i>"; break;
				case "noinvoice": echo "<i class=\"dashicons dashicons-no tips\" data-tip=\"" . esc_attr__( 'No Invoice', WCPDF_IT_DOMAIN ) . "\"></i>"; break;
				default: echo "-"; break;
			}
		}
		return $column;
	}
}
endif;

if(!function_exists('wcpdf_it_get_billing_invoice_type')) include_once "includes/wcpdf-it-functions.php";

if(!function_exists('WCPDF_IT')) {
	function WCPDF_IT() {
		return WooCommerce_Italian_add_on::instance();
	}

	WCPDF_IT(); // load plugin
}

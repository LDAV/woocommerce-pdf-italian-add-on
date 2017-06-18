<?php
/**
 * Plugin Name: WooCommerce PDF Invoices Italian Add-on
 * Plugin URI: http://ldav.it/plugin/woocommerce-pdf-invoices-italian-add-on/
 * Description: Italian Add-on for PDF invoices & packing slips for WooCommerce.
 * Version: 0.4.9.4
 * Author: laboratorio d'Avanguardia
 * Author URI: http://ldav.it/
 * License: GPLv2 or later
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 * Text Domain: woocommerce-pdf-invoices-italian-add-on
 * Domain Path: /languages

*/

//Thanks to Nicola Mustone https://gist.github.com/SiR-DanieL

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( !class_exists( 'WooCommerce_Italian_add_on' ) ) :
define('WCPDF_IT_DOMAIN', 'woocommerce-pdf-italian-add-on');

class WooCommerce_Italian_add_on {
	public $plugin_basename;
	public $plugin_url;
	public $plugin_path;
	public $version = '0.4.9.4';
	protected static $instance = null;
	
	public $settings;
	public $options;
	public $eu_vat_countries;
	public $default_country;
	public $regexCF = "/^([A-Z]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST]{1}[0-9LMNPQRSTUV]{2}[A-Za-z]{1}[0-9LMNPQRSTUV]{3}[A-Z]{1})$/i";
	public $regexPIVA = "/^(ATU[0-9]{8}|BE0[0-9]{9}|BG[0-9]{9,10}|CY[0-9]{8}L| CZ[0-9]{8,10}|DE[0-9]{9}|DK[0-9]{8}|EE[0-9]{9}|(EL|GR)[0-9]{9}|ES[0-9A-Z][0-9]{7}[0-9A-Z]|FI[0-9]{8}|FR[0-9A-Z]{2}[0-9]{9}|GB([0-9]{9}([0-9]{3})?|[A-Z]{2}[0-9]{13})|HU[0-9]{8}|IE[0-9]S[0-9]{5}L|IT[0-9]{11}|LT([0-9]{9}|[0-9]{12})|LU[0-9]{8}|LV[0-9]{11}|MT[0-9]{8}|NL[0-9]{9}B[0-9]{2}|PL[0-9]{10}|PT[0-9]{9}|RO[0-9]{2,10}|SE[0-9]{12}|SI[0-9]{8}|SK[0-9]{10})$/i";

	public $invoice_required;
	public $hide_outside_UE;

	public static function instance() {
		if ( is_null( self::$instance ) ) self::$instance = new self();
		return self::$instance;
	}

	public function __construct() {
		$this->plugin_basename = plugin_basename(__FILE__);
		$this->plugin_url = plugin_dir_url($this->plugin_basename);
		$this->plugin_path = trailingslashit(dirname(__FILE__));
		$this->init_hooks();
	}

	public function init() {
		$locale = apply_filters( "plugin_locale", get_locale(), WCPDF_IT_DOMAIN );
		$langdir    = trailingslashit( WP_LANG_DIR );
		load_textdomain( WCPDF_IT_DOMAIN, $langdir . dirname( $this->plugin_basename ). "/" . WCPDF_IT_DOMAIN . "-" . $locale . ".mo" );
		load_textdomain( WCPDF_IT_DOMAIN, $langdir . "plugins/" . WCPDF_IT_DOMAIN . "-" . $locale . ".mo" );
		load_plugin_textdomain( WCPDF_IT_DOMAIN, false, dirname( $this->plugin_basename ) . "/languages" );
	}

	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		if ($this->is_wc_active()) {
			add_action( 'init', array( $this, 'init_integration' ) );
			add_filter( 'woocommerce_billing_fields' , array( $this, 'billing_fields'), 10, 1);
			add_filter( 'woocommerce_admin_billing_fields' , array( $this, 'admin_field_cfpiva' ));
			add_action( 'woocommerce_after_edit_address_form_billing', array( $this, 'after_order_notes') );
			add_action( 'woocommerce_after_order_notes', array( $this, 'after_order_notes') );
			add_action( 'woocommerce_checkout_process', array( $this, 'piva_checkout_field_process'));
			add_filter( 'woocommerce_order_formatted_billing_address' , array( $this, 'woocommerce_order_formatted_billing_address'), 10, 2 );
			add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'my_account_my_address_formatted_address'), 10, 3 );
			add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'formatted_address_replacements'), 10, 2 );
			add_filter( 'woocommerce_localisation_address_formats', array( $this, 'localisation_address_format') );
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
				add_filter( 'woocommerce_found_customer_details', array( $this, 'found_customer_details') );
			} else {
				add_filter( 'woocommerce_ajax_get_customer_details', array( $this, 'ajax_get_customer_details'), 10, 3 );
			}
			add_filter( 'woocommerce_customer_meta_fields', array( $this, 'customer_meta_fields') );

			add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_invoice_type_column' ));
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'invoice_type_column_data' ));

		} else {
			add_action( 'admin_notices', array ( $this, 'check_wc' ) );
		}
	}

	public function is_wc_active() {
		$plugins = get_site_option( 'active_sitewide_plugins', array());
		if (in_array('woocommerce/woocommerce.php', get_option( 'active_plugins', array())) || isset($plugins['woocommerce/woocommerce.php'])) {
			return true;
		} else {
			return false;
		}
	}

	public function check_wc( $fields ) {
		$class = "error";
		$message = sprintf( __( 'WooCommerce Italian Add-on requires %sWooCommerce%s to be installed and activated!' , WCPDF_IT_DOMAIN ), '<a href="https://wordpress.org/plugins/woocommerce/">', '</a>' );
		echo"<div class=\"$class\"> <p>$message</p></div>";
	}	

/*
	public function check_wpo_wcpdf( $fields ) {
		$class = "updated fade";
		$message = sprintf( __( 'WooCommerce Italian Add-on with <strong>%sWooCommerce PDF Invoices & Packing Slips%s</strong> could be more powerful!' , WCPDF_IT_DOMAIN ), '<a href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/">', '</a>' );
		echo"<div class=\"$class\"> <p>$message</p></div>";
	}
*/

	public function init_integration() {
		include_once 'includes/class-wc-settings.php';
		$this->settings = new WooCommerce_Italian_add_on_Settings();
		$this->options = get_option($this->settings->general_settings_key);
		$this->invoice_required = isset($this->options["invoice_required"]) && $this->options["invoice_required"] == "required" ? true: false;
		$this->hide_outside_UE = !empty($this->options["hide_outside_UE"]);

		$this->eu_vat_countries = WC()->countries->get_european_union_countries('eu_vat');
		$this->default_country = WC()->countries->get_base_country();

		if ( in_array( 'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			include_once 'includes/class-wcpdf-integration.php';
		} else {
			//add_action( 'admin_notices', array ( $this, 'check_wpo_wcpdf' ) );
		}
	}

	public function billing_fields( $fields ) {
		$cl1 = $this->options["invoice_width"] == "wide" ? 'form-row-wide' : 'form-row-first';
		$cl2 = $this->options["invoice_width"] == "wide" ? 'form-row-wide' : 'form-row-last';
		$req = $this->options["invoice_required"] == "required" ? true: false;
		
		$fields['billing_invoice_type'] = array(
			'label' => __('Invoice or Receipt', WCPDF_IT_DOMAIN),
			'placeholder' => __( 'Invoice or Receipt', WCPDF_IT_DOMAIN ),
			'required'    => false,
			'class'       => array( $cl1),
			'clear'       => false,
			'type'        => 'select',
			'options'     => array(
				'receipt' => __('Receipt', WCPDF_IT_DOMAIN ),
				'invoice' => __('Invoice', WCPDF_IT_DOMAIN )
			),
			'value'       => get_user_meta( get_current_user_id(), 'billing_invoice_type', true )
		);
		$fields['billing_cf'] = array(
			'label'       => __('VAT number', WCPDF_IT_DOMAIN),
			'placeholder' => __('Please enter your VAT number or Tax Code', WCPDF_IT_DOMAIN),
			'required'    => $req,
			'class'       => array( $cl2 ),
			'value'       => get_user_meta( get_current_user_id(), 'billing_cf', true )
		);
		return $fields;
	}
	
	public function admin_field_cfpiva( $fields ) {
		$fields['invoice_type'] = array(
		'label' => __('Invoice or Receipt', WCPDF_IT_DOMAIN),
		'show' => false,
		'wrapper_class' => 'form-field-wide',
		'type'        => 'select',
		'options'     => array(
			'receipt' => __('Receipt', WCPDF_IT_DOMAIN ),
			'invoice' => __('Invoice', WCPDF_IT_DOMAIN )
			)
		);
		$fields['cf'] = array(
		'label' => __('VAT number', WCPDF_IT_DOMAIN),
		'wrapper_class' => 'form-field-wide',
		'show' => false
		);
	
		return $fields;
	}
	
	public function after_order_notes() {
		$select2 = (wp_script_is( "select2")) ? 'jQuery("#billing_invoice_type").select2({minimumResultsForSearch: Infinity});' : "";
		if(!$this->hide_outside_UE) {
			echo '<script type="text/javascript">
			jQuery(function() {
				' . $select2 . '
			});
			</script>';
		} else {
			//nasconde i campi quando l'indirizzo di fatturazione è fuori dalla UE
			echo '<script type="text/javascript">
			function wcpdf_IT_billing_country_change() {
				if(jQuery("#billing_country").length == 0) return(false);
				if(jQuery.inArray(jQuery("#billing_country").val(), ' . json_encode($this->eu_vat_countries) . ') >= 0) {
					odata = jQuery("#billing_cf_field").data("o_data");
					if(odata) {
						jQuery("#billing_cf_field").addClass(odata.class);
						jQuery("#billing_cf").val(odata.value);
						jQuery("#billing_invoice_type").val(odata.option);
						jQuery("#billing_cf_field").removeData("o_data");
					}
					jQuery("#billing_cf_field").show();
					jQuery("#billing_invoice_type_field").show();
				} else {
					jQuery("#billing_cf_field").data("o_data", {"class" : jQuery("#billing_cf_field").attr("class"), "value" : jQuery("#billing_cf").val(), "option" : jQuery("#billing_invoice_type").val()});
					jQuery("#billing_cf").val("");
					jQuery("#billing_cf_field").removeClass("validate-required woocommerce-invalid woocommerce-invalid-required-field");
					jQuery("#billing_cf_field").hide();
					jQuery("#billing_invoice_type").val("receipt");
					jQuery("#billing_invoice_type_field").hide();
				}
			}
			jQuery(function() {
				' . $select2 . '
				jQuery("#billing_country").change(wcpdf_IT_billing_country_change);
				wcpdf_IT_billing_country_change();
			});
			</script>';
		}
	}
	
	public function piva_checkout_field_process() {
/*
		if(isset($_POST["billing_country"]) && $this->invoice_required && $this->hide_outside_UE) {
			WC()->checkout->checkout_fields['billing']['billing_cf']['required'] = in_array($_POST["billing_country"], $this->eu_vat_countries);
		}
*/
		// Verifica se è presente quando cliccano su acquista
		if($_POST["billing_invoice_type"] == "invoice") {
			if(!trim($_POST['billing_cf'])) {
				wc_add_notice(__('Please enter your VAT number or Tax Code', WCPDF_IT_DOMAIN) ,$notice_type = 'error');
			} elseif($_POST["billing_country"] == 'IT' && strlen($_POST['billing_cf']) > 13) {
				if(!preg_match($this->regexCF, $_POST['billing_cf'])) {
					wc_add_notice(sprintf(__('Tax Identification Number %1$s is not correct', WCPDF_IT_DOMAIN), "<strong>". strtoupper($_POST['billing_cf']) . "</strong>"),$notice_type = 'error');
				}
			} else {
				if(!(preg_match($this->regexPIVA, $_POST["billing_country"].$_POST['billing_cf']))) wc_add_notice(sprintf(__('VAT number %1$s is not correct', WCPDF_IT_DOMAIN), "<strong>". $_POST["billing_country"].$_POST['billing_cf'] . "</strong>"),$notice_type = 'error');
			}
			//if (!$_POST['billing_cf']) wc_add_notice( __('Please enter your VAT number or Tax Code', WCPDF_IT_DOMAIN),$notice_type = 'error');
		}
		if($_POST["billing_invoice_type"] == "receipt" && $_POST['billing_cf'] && $_POST["billing_country"] == 'IT'){
			if(!preg_match($this->regexCF, $_POST['billing_cf']) && !preg_match("/^([0-9]{11})$/i", $_POST['billing_cf'])) {
				wc_add_notice(sprintf(__('Tax Identification Number %1$s is not correct', WCPDF_IT_DOMAIN), "<strong>". strtoupper($_POST['billing_cf']) . "</strong>"),$notice_type = 'error');
			}
		}
	}
	
	public function woocommerce_order_formatted_billing_address( $fields, $order) {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
			$fields['invoice_type'] = $order->billing_invoice_type;
			$fields['cf'] = $order->billing_cf;
		} else {
			$fields['invoice_type'] = $order->get_meta("_billing_invoice_type",true);
			$fields['cf'] = $order->get_meta("_billing_cf",true);
		}
		return $fields;
	}
	
	public function my_account_my_address_formatted_address( $fields, $customer_id, $type ) {
		if ( $type == 'billing' ) {
			$fields['invoice_type'] = get_user_meta( $customer_id, 'billing_invoice_type', true );
			$fields['cf'] = get_user_meta( $customer_id, 'billing_cf', true );
		}
		return $fields;
	}
	
	public function formatted_address_replacements( $address, $args ) {
		$address['{invoice_type}'] = '';
		$address['{cf}'] = '';
	
/*
		if ( !empty( $args['cf']) && !empty( $args['invoice_type'] ) ) {
			$address['{cf}'] = ($args['invoice_type'] == "invoice" ? __('VAT', WCPDF_IT_DOMAIN) . ": " . $args['country'] : __('Tax Code', WCPDF_IT_DOMAIN) . ': ') . strtoupper( $args['cf'] );
		}
*/
		if (!empty($args['cf'])) {
			$pre = preg_match($this->regexCF, $args['cf']) ? __('Tax Code', WCPDF_IT_DOMAIN) . ': ' : __('VAT', WCPDF_IT_DOMAIN) . ": " . $args['country'];
			$address['{cf}'] = $pre . strtoupper($args['cf']);
		}

		return $address;
	}
	
	public function localisation_address_format( $formats ) {
		foreach($this->eu_vat_countries as $codice) {
			$formats[$codice] = isset($formats[$codice]) ? $formats[$codice] . "\n{cf}" : $formats['default'] . "\n{cf}";
		}		
		//$formats['IT'] .= "\n\n{cf}";
		return $formats;
	}

	public function ajax_get_customer_details($data, $customer, $user_id) {
		$data['billing'][$this->field_name] = get_user_meta( $user_id, 'billing_' . $this->field_name, true );
		$data['billing']["cf"] = get_user_meta( $user_id, 'billing_cf', true );
		return $data;
	}

	public function found_customer_details( $customer_data ) {
		$customer_data['billing_invoice_type'] = get_user_meta( $_POST['user_id'], 'billing_invoice_type', true );
		$customer_data['billing_cf'] = get_user_meta( $_POST['user_id'], 'billing_cf', true );
		return $customer_data;
	}
	
	/* Add fields in Edit User Page*/
	public function customer_meta_fields( $fields ) {
		$fields['billing']['fields']['billing_invoice_type'] = array(
			'label'       => __('Invoice or Receipt', WCPDF_IT_DOMAIN),
			'type'        => 'select',
			'options'     => array(
				'receipt' => __('Receipt', WCPDF_IT_DOMAIN ),
				'invoice' => __('Invoice', WCPDF_IT_DOMAIN )
			),
			'description'       => ""
		);
		$fields['billing']['fields']['billing_cf'] = array(
			'label'       => __('VAT number', WCPDF_IT_DOMAIN),
			'description'       => ""
		);
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

	public function invoice_type_column_data( $column ) {
		global $post, $the_order;
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
			if ( empty( $the_order ) || $the_order->id != $post->ID ) $the_order = wc_get_order( $post->ID );
		} else {
			if ( empty( $the_order ) || $the_order->get_id() !== $post->ID ) $the_order = wc_get_order( $post->ID );
		}
		if ( $column === 'invoice_type' ) {
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
				$invoicetype = get_post_meta($the_order->id,"_billing_invoice_type",true);
			} else {
				$invoicetype = $the_order->get_meta("_billing_invoice_type",true);
			}
			switch($invoicetype) {
				case "invoice": echo "<i class=\"dashicons dashicons-media-document tips\" data-tip=\"" . __( 'invoice', WCPDF_IT_DOMAIN ) . "\"></i>"; break;
				case "receipt": echo "<i class=\"dashicons dashicons-media-default tips\" data-tip=\"" . __( 'receipt', WCPDF_IT_DOMAIN ) . "\"></i>"; break;
				default: echo "-"; break;
			}
		}
		return $column;
	}
}
endif;

$wcpdf_IT = new WooCommerce_Italian_add_on();

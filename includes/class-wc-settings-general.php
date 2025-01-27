<?php
use Automattic\WooCommerce\Utilities\OrderUtil;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooCommerce_Italian_add_on_Settings_general' ) ) {

	class WooCommerce_Italian_add_on_Settings_general {

		private $invoice_meta_key_name;
	
		public function __construct() {
			add_action( 'admin_init', array(&$this, 'init_settings' ));
			add_action( 'wcpdf_IT_general_settings_output', array( $this, 'output' ) );
			if ( !class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) || !OrderUtil::custom_orders_table_usage_is_enabled() ) {
				add_action( 'views_edit-shop_order', array( $this, 'views_edit_shop_order') );
			}
			add_filter( 'request', array( $this, 'request_query_filter_by_invoice_exists' ) );
			$this->invoice_meta_key_name = class_exists( 'WPO_WCPDF' ) ? "_wcpdf_invoice_number" : "woo_pdf_invoice_id";
		}

		public function request_query_filter_by_invoice_exists( $query_vars ) {
			global $typenow;
			if ( is_admin() && $typenow == 'shop_order' && !empty($_GET["invoiced"])) {
				if($_GET["invoiced"] == "1") {
					$query_vars = array_merge( $query_vars, array(
						'meta_key'  => $this->invoice_meta_key_name,
						'compare'   => 'EXISTS',
					) );
				} elseif($_GET["invoiced"] == "0") {
					$query_vars = array_merge( $query_vars, array(
						'meta_query' => array(
								'relation' => 'OR',
								array(
										'key' => $this->invoice_meta_key_name,
										'value' => '',
										'type' => 'string'
								),
								array(
										'key' => $this->invoice_meta_key_name,
										'compare' => 'NOT EXISTS'
								)
						)
					) );

				
				}
			}

			return $query_vars;
		}

		function views_edit_shop_order($views) {
			if (!isset($_GET['post_type']) || sanitize_text_field( $_GET['post_type'] ) !='shop_order') return false;

			global $wpdb;
			$invoice_count = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM {$wpdb->postmeta} WHERE meta_key = %s", $this->invoice_meta_key_name ) );
			$qs = "?" . str_replace("&invoiced=1", "", $_SERVER['QUERY_STRING']) . "&invoiced=1";

			$views["invoiced"] = '<a href="' . $_SERVER['SCRIPT_NAME'] . $qs . '">' . __("Invoiced", WCPDF_IT_DOMAIN) . ' <span class="count">(' . $invoice_count . ')</span></a>';
			return $views;
		}

		public function output( $section ) {
			wp_enqueue_style('woocommerce_admin_styles', plugins_url() . 'woocommerce/assets/css/admin.css');
			wp_register_script( 'selectWoo', plugins_url() . 'woocommerce/assets/js/selectWoo/selectWoo.full.min.js' );
			wp_enqueue_script( 'selectWoo' );
			wp_register_script( 'wc-enhanced-select', plugins_url() . 'woocommerce/assets/js/admin/wc-enhanced-select.min.js' );
			wp_enqueue_script( 'wc-enhanced-select' );
			settings_fields( WCPDF_IT()->settings->general_settings_key );
			do_settings_sections( WCPDF_IT()->settings->general_settings_key );
			submit_button();
?>
<script type="text/javascript">
function wcpdf_IT_show_pnl_receipt_allowed(){
	if(jQuery("input[id^='wcpdf_IT_general_settings[invoice_required][required]']").prop("checked")){
		jQuery("input[id^='wcpdf_IT_general_settings[receipt_allowed][receipt]']").prop("checked", false);
		jQuery("input[id^='wcpdf_IT_general_settings[receipt_allowed][noinvoice]']").prop("checked", true);
		jQuery("input#invoice_not_required_categories").removeAttr("disabled");
		jQuery("tr.receipt_allowed input[type='radio']").attr("disabled", "disabled");
	}
	if(jQuery("input[id^='wcpdf_IT_general_settings[invoice_required][not-required]']").prop("checked")){
		jQuery("input#invoice_not_required_categories").attr("disabled", "disabled").val("");
		jQuery("tr.receipt_allowed input[type='radio']").removeAttr("disabled");
	}
}
jQuery(function() {
	jQuery("#wpfooter").prepend("<p class=\"alignleft\"><em><?php echo sprintf(esc_attr__("If you like <strong>%1s</strong> please leave us a %2s rating. A huge thank you from %3s team in advance!", WCPDF_IT_DOMAIN ), "WooCommerce Italian Add-on", "<a href='https://wordpress.org/support/view/plugin-reviews/woocommerce-pdf-invoices-italian-add-on?rate=5#postform'>★★★★★</a>", "<a href='http://ldav.it'>laboratorio d'Avanguardia</a>"); ?></em></p><div class=\"clear\"></div>");
	jQuery("input[id^='wcpdf_IT_general_settings[invoice_required]']").click(wcpdf_IT_show_pnl_receipt_allowed);
	wcpdf_IT_show_pnl_receipt_allowed();
	if(typeof(jQuery.fn.select2) === "function") {
		jQuery(".form-table select").each(function (){
			var placeholder = jQuery(this).data("placeholder");
			jQuery(this).select2({
				"placeholder": placeholder,
			});
		});
	}

});
</script>
<?php
		}

		function init_settings(){
			$page = $section = $option_name = WCPDF_IT()->settings->general_settings_key;
			$fields = array(
				array(
					'title' => __( 'VAT/Tax Code always required', WCPDF_IT_DOMAIN ),
					'callback' => 'radio_element_callback',
					'args' => array(
						'option_name' => $option_name,
						'id' => 'invoice_required',
						'description' => sprintf("%s<table class='wp-list-table widefat fixed striped'><thead><tr><td>%s</td><td>%s</td><td>%s</td></tr></thead><tbody><tr><td>%s</td><td>%s</td><td>%s</td></tr><tr><td>%s</td><td>%s</td><td>%s</td></tr><tr><td>%s</td><td>%s</td><td>%s</td></tr></tbody></table>", __( "Choose if you want that VAT/Tax Code is always a required field. If NOT always required, it follows these rules:", WCPDF_IT_DOMAIN), __("Customer's billing country", WCPDF_IT_DOMAIN), __("User selection: Invoice", WCPDF_IT_DOMAIN), __("User selection: Receipt", WCPDF_IT_DOMAIN), __("Italy", WCPDF_IT_DOMAIN), __("VAT/Tax Code required", WCPDF_IT_DOMAIN), __("not required", WCPDF_IT_DOMAIN), __("UE", WCPDF_IT_DOMAIN), __("VAT required", WCPDF_IT_DOMAIN), __("not required", WCPDF_IT_DOMAIN), __("Extra UE", WCPDF_IT_DOMAIN) . " <sup>(1)</sup>", __("VAT required/not required", WCPDF_IT_DOMAIN) . " <sup>(2)</sup>", __("not required", WCPDF_IT_DOMAIN) ),
						'default' => 'not-required',
						'options' => array(
							'required'	=> __( 'always required' , WCPDF_IT_DOMAIN ),
							'not-required'	=> __( 'depends on billing country and user selection' , WCPDF_IT_DOMAIN ),
						),
						'class' => "invoice_required"
					)
				),
				array(
					'title' => "<sup>(1)</sup> " . __( 'Hide the Italian Add-on fields if billing country is outside UE?', WCPDF_IT_DOMAIN ),
					'callback' => 'radio_element_callback',
					'args' => array(
						'option_name' => $option_name,
						'id' => 'hide_outside_UE',
						'description' => __( "Choose if you want to hide the Italian Add-on fields if customer's billing country is outside UE", WCPDF_IT_DOMAIN ),
						'default' => 0,
						'options' => array(
							0	=> __( "Always show Italian Add-on fields" , WCPDF_IT_DOMAIN ),
							1	=> __( "Hide Italian Add-on fields if customer's billing country is outside UE" , WCPDF_IT_DOMAIN ),
						),
						'class' => "hide_outside_UE"
					)
				),
				array(
					'title' => "<sup>(2)</sup> " . __( 'Always request VAT number for non-EU customers invoices', WCPDF_IT_DOMAIN ),
					'callback' => 'radio_element_callback',
					'args' => array(
						'option_name' => $option_name,
						'id' => 'invoice_required_non_UE',
						'description' => __( "Choose whether you want to make it compulsory to enter a VAT number for non-EU customers invoices.", WCPDF_IT_DOMAIN ),
						'default' => 0,
						'options' => array(
							1	=> __( 'required' , WCPDF_IT_DOMAIN ),
							0	=> __( 'not required' , WCPDF_IT_DOMAIN ),
						)
					)
				),
				array(
					'title' => __( 'If customer does not choose between invoice or receipt', WCPDF_IT_DOMAIN ),
					'callback' => 'radio_element_callback',
					'args' => array(
						'option_name' => $option_name,
						'id' => 'what_if_no_invoicetype',
						'description' => __( "What to do if customer doesn't choose between invoice or receipt (e.g. if customer pays directly by Paypal Express)", WCPDF_IT_DOMAIN ),
						'default' => "noinvoice",
						'options' => array(
							"noinvoice"	=> __( "Don't issue any invoice or receipt" , WCPDF_IT_DOMAIN ),
							"invoice"	=> __( 'Issue an invoice' , WCPDF_IT_DOMAIN ),
							"receipt"	=> __( 'Issue a receipt' , WCPDF_IT_DOMAIN ),
						)
					)
				),

/*
				array(
					'title' => __( 'Additional field for the tax code if Italian company', WCPDF_IT_DOMAIN ),
					'callback' => 'radio_element_callback',
					'args' => array(
						'option_name' => $option_name,
						'id' => 'add_cf2',
						'description' => __( "It adds an additional field for the tax code when the customer is an Italian company.", WCPDF_IT_DOMAIN ),
						'default' => "0",
						'options' => array(
							"0"	=> __( "No" , WCPDF_IT_DOMAIN ),
							"1"	=> __( "Add an optional tax code field" , WCPDF_IT_DOMAIN ),
							"2"	=> __( "Add a mandatory tax code field" , WCPDF_IT_DOMAIN ),
						)
					)
				),
*/
				array(
					'title' => __( 'VAT/Tax Code field position', WCPDF_IT_DOMAIN ),
					'callback' => 'select_element_callback',
					'args' => array(
						'option_name' => $option_name,
						'id' => 'fields_priority',
						'description' => __( "Choose after which field in the checkout page do you want to place invoice/receipt and VAT/Tax Code fields", WCPDF_IT_DOMAIN ),
						'default' => '120',
						'options' => array(
							'25'	=> __( 'after Last Name field' , WCPDF_IT_DOMAIN ),
							'35'	=> __( 'after Company field' , WCPDF_IT_DOMAIN ),
							'95'	=> __( 'after City/State field' , WCPDF_IT_DOMAIN ),
							'105'	=> __( 'after Phone field' , WCPDF_IT_DOMAIN ),
							'120'	=> __( 'after Email field' , WCPDF_IT_DOMAIN ),
						),
						'class' => "fields_priority"
					)
				),
				array(
					'title' => __( 'VAT/Tax Code field width', WCPDF_IT_DOMAIN ),
					'callback' => 'radio_element_callback',
					'args' => array(
						'option_name' => $option_name,
						'id' => 'invoice_width',
						'description' => __( "Choose if you want that fields VAT/Tax Code and invoice/receipt on the same row in checkout page", WCPDF_IT_DOMAIN ) . '<br><img src="' . WooCommerce_Italian_add_on::$plugin_url . 'images/invoice_width.png" width="464" height="259" />',
						'default' => 'wide',
						'options' => array(
							'wide'	=> __( 'wide' , WCPDF_IT_DOMAIN ),
							'short'	=> __( 'short' , WCPDF_IT_DOMAIN ),
						),
						'class' => "invoice_width"
					)
				),
			);
			if ( defined('WPO_WCPDF_TEMPLATES_VERSION') && version_compare( WPO_WCPDF_TEMPLATES_VERSION, '2.4', '>' ) ) {
				if ( defined('WPO_WCPDF_VERSION') && version_compare( WPO_WCPDF_VERSION, '2.0', '>' ) ) {
					$default = basename( WPO_WCPDF()->settings->get_template_path() );
				} else {
					$default = 'Simple Premium';
				}
				$fields[] = array(
					'title' => __( 'Template', WCPDF_IT_DOMAIN ),
					'callback' => 'select_element_callback',
					'args' => array(
						'option_name' => $option_name,
						'id' => 'template_name',
						'description'	=> __( "Select the base template you want to use", WCPDF_IT_DOMAIN ),
						'default'		=> $default,
						'options' => array(
							'Simple Premium'	=> 'Simple Premium',
							'Business'			=> 'Business',
							'Modern'			=> 'Modern',
							'Simple'			=> 'Simple',
						),
					)
				);
			}

			$section_settings[] = array(
				'title' => __( 'General settings', WCPDF_IT_DOMAIN ),
				'id' => 'general_settings',
				'fields' => $fields
			);

			WCPDF_IT()->settings->add_settings_fields( $page, $section_settings, $section);
			return;
		}
		
	} // end class

} // end class_exists

return new WooCommerce_Italian_add_on_Settings_general();

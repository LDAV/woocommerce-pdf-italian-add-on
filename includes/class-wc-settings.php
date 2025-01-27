<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooCommerce_Italian_add_on_Settings' ) ) {

	class WooCommerce_Italian_add_on_Settings {
	
		private $options_page_hook;
		public $general_settings_key = 'wcpdf_IT_general_settings';
		public $fpa_settings_key = 'wcpdf_IT_fpa_settings';
		public $idFiscali_settings_key = 'wcpdf_IT_idFiscali_settings';
		//public $albo_settings_key = 'wcpdf_IT_albo_settings';
		//public $rea_settings_key = 'wcpdf_IT_rea_settings';
		//public $export_orders_page_key = 'wcpdf_IT_export_orders_page';
		public $premium_versions_key = 'wcpdf_IT_premium_versions';
		public $invoice_templates_key = 'wcpdf_IT_invoice_templates';
		private $plugin_options_key = 'wcpdf_IT_options_page';
		private $plugin_settings_tabs = array();
		public $setting_descriptions = array();
		
		private $callbacks;
		private $general_settings;
		private $fpa_settings;
		//private $export_orders_page;
		private $premium_settings;
		private $invoice_templates_settings;
	
		public function __construct() {
			$this->callbacks = include( 'class-wc-settings-callbacks.php' );
			$this->general_settings = include( 'class-wc-settings-general.php' );
			$this->fpa_settings = include( 'class-wc-settings-fpa.php' );
			//$this->export_orders_page = include( 'class-wc-settings-export.php' );
			$this->premium_settings = include( 'class-wc-settings-premium.php' );
			$this->invoice_templates_settings = include( 'class-wc-settings-invoice-template.php' );

			add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );
			add_action( 'admin_init', array( &$this, 'register_general_settings' ));
			add_filter( 'plugin_action_links_'. WooCommerce_Italian_add_on::$plugin_basename, array( &$this, 'add_settings_link' ) );
		}

		public function add_admin_menus() {
			$parent_slug = 'woocommerce';
			
			$this->options_page_hook = add_submenu_page(
				$parent_slug,
				__( 'Italian Add-on', WCPDF_IT_DOMAIN ),
				__( 'Italian Add-on', WCPDF_IT_DOMAIN ),
				'manage_woocommerce',
				$this->plugin_options_key,
				array( $this, 'settings_page' )
			);
		}
		
		function plugin_options_tabs() {
			$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_settings_key;
			echo '<h2 class="nav-tab-wrapper">';
			echo '<i class="icon icon32"></i>';
			foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
				$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
				echo '<a class="nav-tab ' . esc_attr($active) . '" href="?page=' . esc_attr($this->plugin_options_key) . '&tab=' . esc_attr($tab_key) . '">' . esc_attr($tab_caption) . '</a>';
			}
			echo '</h2>';
		}

		public function settings_page() {
			$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET[ 'tab' ] ) : $this->general_settings_key;
?>
<h2><img src="<?php echo esc_attr(WooCommerce_Italian_add_on::$plugin_url) ?>images/logo2.png" width="36" style="vertical-align: middle; margin-right: 12px"> WooCommerce PDF Invoice Italian Add-on <sup><?php echo esc_attr(WCPDF_IT()->version) ?></sup></h2>
<div class="wrap woocommerce">
<?php $this->plugin_options_tabs(); ?>
<form method="post" action="options.php" id="wcpdf-IT-settings">
<?php do_action( $tab.'_output' ); ?>
</form>
</div>
<style>
#wpbody-content, .wrap.woocommerce, .wrap.woocommerce td{font-family: "Helvetica Neue", Helvetica, Arial, "sans-serif"}
#wpbody-content h2{font-size: 1.5em; font-weight: 400}
#wpbody-content h2 sup{font-size: 0.5em}
#wpbody-content .wrap.woocommerce h2{font-size: 1.3em; font-weight: 500}
#wpbody-content .wrap.woocommerce h3{font-size: 1.2em; font-weight: 500}
#wpbody-content .nav-tab{font-weight: 400}
#wpbody-content .nav-tab.nav-tab-active{font-weight: 700}
</style>
<?php
		}
		
		public function register_general_settings() {
			$this->plugin_settings_tabs[$this->general_settings_key] = __( 'Settings', WCPDF_IT_DOMAIN );
			$this->plugin_settings_tabs[$this->fpa_settings_key] = __( 'Electronic Invoicing', WCPDF_IT_DOMAIN );
			//$this->plugin_settings_tabs[$this->export_orders_page_key] = __( 'Data export', WCPDF_IT_DOMAIN );
			$this->plugin_settings_tabs[$this->premium_versions_key] = __( 'Premium Versions', WCPDF_IT_DOMAIN );
			$this->plugin_settings_tabs[$this->invoice_templates_key] = __( 'Invoice Templates', WCPDF_IT_DOMAIN );
		}
	
		public function add_settings_link( $links ) {
			$settings_link = '<a href="admin.php?page=' . $this->plugin_options_key . '">'. __( 'Settings', WCPDF_IT_DOMAIN ) . '</a>';
			array_push( $links, $settings_link );
			return $links;
		}

		public function add_settings_fields( $page, $section_settings, $section  ) {
			foreach($section_settings as $section_setting) {
				if(!isset($section_setting["id"])) {
					error_log(var_export($section_setting, true));
				}
				$idsection = $section_setting["id"];
				
				if( isset($section_setting["description"] ) ) $this->setting_descriptions[$idsection] = $section_setting["description"];
				//add_settings_section( $id, $title, $callback, $page );
				add_settings_section(
					$idsection,
					$section_setting['title'],
					array( $this->callbacks, 'section_options_callback' ),
					$page
				);
				$fields = $section_setting["fields"];
				foreach ( $fields as $field ) {
					//add_settings_field( $id, $title, $callback, $page, $section, $args );
					add_settings_field(
						$field['args']['id'],
						$field['title'],
						array( $this->callbacks, $field['callback'] ),
						$page,
						$idsection,
						$field['args']
					);
				}
			}
			register_setting( $page, $section );
			add_filter( 'option_page_capability_'.$section, array( $this, 'settings_capabilities' ) );
		}
		
		public function settings_capabilities() {
			return 'manage_woocommerce';
		}
	
	} // end class

} // end class_exists